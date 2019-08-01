<?php

class ExcelHandle{
    private $excelObj;

    public function __construct(){
        \think\Loader::import('PHPExcel.PHPExcel');
        $this->excelObj=new \PHPExcel();
    }

    /**
     * @param $title 标题和文件名
     * @param $cellArray  列名数组
     * @param $tableData 数据
     */
    public function exportExcel($title,$cellArray,$tableData){

        $this->excelObj->getProperties()->setTitle($title)->setSubject($title)->setDescription($title);
        $sheet = $this->excelObj->getActiveSheet();
        $sheet->setTitle('sheet1');
        $cellNoArray = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];
        foreach($cellArray as $key=>$cell){
            //绑定列
            $cellArray[$key]['cellNo'] = $cellNo = $cellNoArray[$key];
            $sheet->setCellValue($cellNo.'1',$cell['cellName']);
            if(!empty($cell['width']) && strtolower($cell['width'])!='auto'){
                $sheet->getColumnDimension($cellNo)->setWidth($cell['width']);
                $sheet->getColumnDimension($cellNo)->setWidth($cell['width']);
            }else{
                $sheet->getColumnDimension($cellNo)->setAutoSize(true);
            }
            $sheet->getStyle($cellNo.'1')->getFont()->setBold(true)->setSize(12);
        }
        foreach($tableData as $key => $row){
            $line = $key+2;
            foreach($cellArray as $cell){
                $sheet->setCellValueExplicit($cell['cellNo'].$line,$row[$cell["dataKeyName"]],'s');
            }
        }
        ob_end_clean(); //清除缓冲区,避免乱码
        ob_start(); // Added by me
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($this->excelObj,'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}