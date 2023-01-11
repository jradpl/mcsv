<?php

namespace App\Controller;

use League\Csv\Statement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\Exception;

class MainController extends AbstractController
{
    #[Route('/', name: "index")]
    public function index(): Response
    {
        return $this->redirectToRoute('main');
    }

    #[Route('/main', name: 'main')]
    public function main(): Response
    {
        return $this->render('main.html.twig', [

        ]);
    }

    #[Route('/panel', name: 'panel')]
    public function panel(): Response
    {
        $csv = $this->loadAllCSVRows();
        //dd($csv);
        return $this->render('panel.html.twig', [
            'csv' => $csv
        ]);
    }

    #[Route('delete/{id}', methods: ['GET', 'DELETE'], name: 'delete_row')]
    public function delete($id): Response
    {
        $this->deleteFile($id);
        $this->deleteRow($id);
        return $this->redirectToRoute('panel');
    }

    #[Route('edit/{id}', name: 'edit_row')]
    public function edit($id): Response
    {
        $row_data = $this->get_row_from_line($id);
        return $this->render('edit.html.twig', [
            "row" => $row_data
        ]);
    }

    #[Route('save/{id}', name: 'save_row')]
    public function save($id, Request $request): Response
    {
        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('edit', $submittedToken)) {
            if($request->files->get('file')){
                $filePath = $this->moveUploadFile($request);
            }else{
                $row_data = $this->get_row_from_line($id);
                $filePath = $row_data[5];
            }
            $arrayToSave = $this->makeArrayToSaveNewRow($id, $_POST["name"], $_POST["surname"], $_POST["email"], $_POST["tel"], $filePath);
            $this->editRow($id, $arrayToSave);
        }
        return $this->redirectToRoute('panel');
    }

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('main', $submittedToken)) {
            if($request->files->get('file')){
                $filePath = $this->moveUploadFile($request);
            }else{
                
                $filePath = "";
            }

            $new_id = uniqid();
            $arrayToSave = $this->makeArrayToSaveNewRow($new_id, $_POST["name"], $_POST["surname"], $_POST["email"], $_POST["tel"], $filePath);
            try {
                $writer = Writer::createFromPath('../public/csv/base.csv', 'a+');
                $writer->setNewline("\n");
                $writer->getNewline();
                $writer->insertOne($arrayToSave);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        return $this->redirectToRoute('main');
    }

    protected function loadAllCSVRows()
    {
        try {
            $csv = Reader::createFromPath('../public/csv/base.csv', 'r');
            $csv->setDelimiter(',');
            $csv->setHeaderOffset(0);
            $csvarray = Statement::create()->process($csv);
            return $csvarray;
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    protected function deleteRow($id)
    {
        $delete = $id;

        $data = file('../public/csv/base.csv');

        $out = array();

        foreach ($data as $line) {
            $line_to_check = trim($line);
            $pattern = "/^" . $delete."/i";
            if (!preg_match($pattern,$line_to_check)) {
                $out[] = $line;
            }
        }

        $fp = fopen('../public/csv/base.csv', 'w+');
        flock($fp, LOCK_EX);
        foreach ($out as $line) {
            fwrite($fp, $line);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    protected function get_row_from_line($id){
        $edit = $id;

        $data = file('../public/csv/base.csv');

        foreach ($data as $line) {
            $line_to_check = trim($line);
            $pattern = "/^" . $edit."/i";
            if (preg_match($pattern,$line_to_check)) {
                $line_arr = explode(',', $line_to_check);
                return $line_arr;
            }
            
        }
    }

    protected function editRow($id,$edited_row){
        $edit = $id;

        $data = file('../public/csv/base.csv');

        $out = array();

        foreach ($data as $line) {
            $line_to_check = trim($line);
            $pattern = "/^" . $edit."/i";
            if (preg_match($pattern,$line_to_check)) {
                $line = implode(',', $edited_row);
                $line = $line . "\n";
                $out[] = $line;
            }else{
                $out[] = $line;
            }
        }

        $fp = fopen('../public/csv/base.csv', 'w+');
        flock($fp, LOCK_EX);
        foreach ($out as $line) {
            fwrite($fp, $line);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    protected function makeArrayToSaveNewRow($id, $name, $surname, $email, $telephone, $fileUrl = " ")
    {
        return array($id, $name, $surname, $email, $telephone, $fileUrl);

    }

    protected function moveUploadFile($request){
        $file = $request->files->get('file');
        $filePath = $file->getRealPath();
        $fileName = $file->getClientOriginalName();
        if($filePath){

            $newFileName = uniqid() . '.' . $fileName;

            try{
                move_uploaded_file($filePath,
                    $this->getParameter('kernel.project_dir') . '/public/files/'.$newFileName
                );
            }catch(FileException $e){
                return new Response($e->getMessage());
            }

            return $filePath = '/files/' . $newFileName;
        }
    }

    protected function deleteFile($id){
        $arr = $this->get_row_from_line($id);
        $fp = '.'.$arr[5];
        unlink($fp);

        
    }




}