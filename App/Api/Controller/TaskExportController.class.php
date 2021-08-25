<?php

namespace Api\Controller;

use Common\Service\ReportService;
use Think\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class TaskExportController extends Controller
{

    public function exportData(){
        $param = I('get.');
        
        $report = new ReportService();
        $data = $report->getBaseExportData($param);
        $fileName = time();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$fileName.'.xlsx"');
        header('Cache-Control: max-age=0');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        ob_clean();    //解决文件损坏

        //设置工作表标题名称
        $worksheet->setTitle('工作表格1');
        $worksheet->getColumnDimension('A')->setWidth(20);
        $worksheet->getColumnDimension('B')->setWidth(30);
        $worksheet->getColumnDimension('C')->setWidth(20);
        $worksheet->getColumnDimension('D')->setWidth(20);
        $worksheet->getColumnDimension('E')->setWidth(20);
        $worksheet->getColumnDimension('F')->setWidth(20);
        $worksheet->getColumnDimension('G')->setWidth(20);
        $worksheet->getColumnDimension('H')->setWidth(20);
        //表头
        //设置单元格内容
        foreach ($data['title'] as $key => $value) {
            $worksheet->setCellValueByColumnAndRow($key+1, 1, $value);
        }

        $row = 2; //从第二行开始
        foreach ($data['data'] as $item) {
            $column = 1;
            foreach ($item as $value) {
                $worksheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        }
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        //删除清空：释放内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

}
