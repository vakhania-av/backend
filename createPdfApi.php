<?php

class Melissa_BusinessLogic_Procedures_Reloaded_CreatePdfApi 
    extends Melissa_BusinessLogic_Procedures_Procedure 
    implements Melissa_BusinessLogic_CommonInterface {

    protected function handleGetRequest() {
        
        // Подключаем модули, получаем данные для печати     
        $callId = filter_input(INPUT_GET, 'nameImg'); 
        $width = filter_input(INPUT_GET, 'width');
        $height = filter_input(INPUT_GET, 'height');
        $orientation = filter_input(INPUT_GET, 'orientation');
        $scale = filter_input(INPUT_GET, 'scale');
        
        if (!$callId || !$width || !$height) {
            throw new ErrorException("Не указаны необходимые параметры");
        }

        //получение изображения base64 из БД
        $dataBaseConnect = new Melissa_Service_Database_Pgsql();
        $sqlQuery = "SELECT data FROM s_content.tmp_data WHERE name_full = '" . $callId . "'";
        $res = $dataBaseConnect->get_result($sqlQuery);
        $img = $res[0]['data'];

        // удаление записи с изображением base64 в базе
        $sqlQuery = "DELETE FROM s_content.tmp_data WHERE name_full = '" . $callId . "'";
        $resdel = $dataBaseConnect->get_result($sqlQuery);

        ob_end_clean();

        //подключаем tcpdf
        require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/BusinessLogic/PdfGenerator/tcpdf/tcpdf.php';
        
        //Если ориентация не выбрана пользователем, высчитываем ориентацию по соотношению сторон
        if(!$orientation) { 
            $orientation = $width > $height ? 'L' : 'P';
        }

        //формат страницы А4
        $pageLayout = 'A4';

        // задаем массив отступов
        $paddings = array(
            "top" => 15,
            "bottom" => 30,
            "left" => 20,
            "right" => 15
        );

        $pdf = new TCPDF($orientation, 'px', $pageLayout, true, 'UTF-8', false);

        // убираем на всякий случай шапку и футер документа и устанавливаем отступы страниц
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins($paddings["left"], $paddings["top"], $paddings["right"]); // устанавливаем отступы страниц
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->SetAutoPageBreak(TRUE, 0);

        $imgdata = str_replace(' ', '+', $img);
        $imgdata = base64_decode($imgdata);

        $img = new Imagick();

        //получаем размеры страницы
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        //вычитаем из размеров страницы отступы
        $pageWidth -= ($paddings["left"] + $paddings["right"] + 15);
        $pageHeight -= ($paddings["top"] + $paddings["bottom"]);

        // 1 - вписать в страницу, 2 - вписывать по ширине, 3 - вписывать по высоте
        if($scale == "2") { // вписать только по ширине

            // Коэффициент соотношения ширин страницы и изображения
            $ratio = $width / $pageWidth;

            //Высота страницы (*) в соотношении с высотой изображения
            $heightPageRatio = $pageHeight * $ratio;

            //Если высота изображения больше, чем высота страницы*
            if($height > $heightPageRatio) {

                $delta = $height / $heightPageRatio;

                //Количество целых изображений на странице
                $count = floor($delta);

                if(is_int($delta)) {
                    $balance = 0;
                } else {
                    $balance = $height - ($heightPageRatio * $count);
                }

                for($i = 0; $i < $count; $i++) {
                    $img->clear();
                    $img->readimageblob($imgdata);

                    $pdf->AddPage();

                    $img->cropImage($width, $heightPageRatio, 0, $heightPageRatio * $i);
                    $image = $img->getImagesBlob();

                    $pdf->Image('@' . $image, null, null, $pageWidth, $pageHeight);
                    $k = $i;
                }

                if($balance) {
                    $img->clear();
                    $img->readimageblob($imgdata);

                    $pdf->AddPage();

                    $img->cropImage($width, $balance, 0, $heightPageRatio * ($k + 1));

                    $image = $img->getImagesBlob();
                    
                    $pdf->Image('@' . $image, null, null, $pageWidth, $balance / $ratio);

                }
            } else {
                $pdf->AddPage();
                $pdf->Image('@' . $imgdata, null, null, $pageWidth, $height / $ratio);
            }
            
        } else if($scale == "3") { //вписать только по высоте

            // Коэффициент соотношения ширин страницы и изображения
            $ratio = $height / $pageHeight;

            //Ширина страницы (*) в соотношении с шириной изображения
            $widthPageRatio = $pageWidth * $ratio;

            //Если ширина изображения больше, чем ширина страницы*
            if($width > $widthPageRatio) {
                
                $delta = $width / $widthPageRatio;

                //Количество целых изображений на страницы
                $count = floor($delta);

                if(is_int($delta)) {
                    $balance = 0;
                } else {
                    $balance = $width - ($widthPageRatio * $count);
                }

                for($i = 0; $i < $count; $i++) {
                    $img->clear();
                    $img->readimageblob($imgdata);

                    $pdf->AddPage();

                    $img->cropImage($widthPageRatio, $height, $widthPageRatio * $i, 0);
                    $image = $img->getImagesBlob();

                    $pdf->Image('@' . $image, null, null, $pageWidth, $pageHeight);
                    $k = $i;
                }

                if($balance) {
                    $img->clear();
                    $img->readimageblob($imgdata);

                    $pdf->AddPage();

                    $img->cropImage($balance, $height, $widthPageRatio * ($k + 1), 0);

                    $image = $img->getImagesBlob();
                    
                    $pdf->Image('@' . $image, null, null, $balance / $ratio, $pageHeight);

                }

            } else {
                $pdf->AddPage();
                $pdf->Image('@' . $imgdata, null, null, $width / $ratio, $pageHeight);
            }

        } else if($scale == "1") { // Если вписать в одну страницу

            $pdf->AddPage();

            //Рассчитываем соотношение сторон изображения и страницы
            $deltaImg = $width / $height;
            $deltaPage = $pageWidth / $pageHeight;

            // Если изображение и страница пропорциональны друг другу, то просто добавляем изображение с размерами страницы
            if ($deltaImg == $deltaPage) {
                $wImg = $pageWidth;
                $hImg = $pageHeight;
            } else if ($deltaImg > $deltaPage) { //вписываем изображение во всю ширину
                $wImg = $pageWidth;
                $hImg = $pageWidth * $height / $width;
            } else if ($deltaImg < $deltaPage) { //вписываем изображение во всю высоту
                $wImg = $pageHeight * $width / $height;
                $hImg = $pageHeight;
            }

            $pdf->Image('@' . $imgdata, null, null, $wImg, $hImg);
        }

        $img->destroy();

        $pdf->Output('e.pdf', 'I');
        exit;

        return $this->responseData;
    }

    public function execute() {
        $this->initialize();

        $request = Melissa_Service_Http_Request::createFromGlobals();
        $method = $request->getHttpMethod();

        if ($method === 'POST') {
            throw new ErrorException("Процедура доступна только через api");
            $inputData = $request->getInputData();
            $req = '';

            $data = Melissa_Service_Json::decode($inputData['data']);
            $input = file_get_contents('php://input');
            return $this->handleCommandRequest($req, $input);
        }

        parent::execute();
    }

    /**
     * Обработка комнад
     */
    private function handleCommandRequest($command, $data) {
        $i = 0;
    }

}
