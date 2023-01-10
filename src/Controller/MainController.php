<?php

namespace App\Controller;

use League\Csv\Statement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use League\Csv\Reader;
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
        $row_id = $id;
        $this->deleteRow($row_id);

        return $this->redirectToRoute('panel');
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
        $file_handle = fopen("../public/csv/base.csv", "r+");

        while (!feof($file_handle)) {
            $line_of_text = fgetcsv($file_handle, 1024);
            $text = $line_of_text;
                if ($id == $text) {
                    fputcsv($file_handle, $line_of_text);
                }
        }
        fclose($file_handle);
    }





}