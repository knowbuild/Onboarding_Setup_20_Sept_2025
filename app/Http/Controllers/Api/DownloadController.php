<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;

class DownloadController extends Controller
{
    public function downloadPdf(Request $request)
    {
        
        
        $po_id = $request->query('po_id', 0); 
        
        $response = Http::withOptions(['verify' => false])->get('https://laravelapi.knowbuild.com/laravelapi/api/purchase/purchase-orders-details', [
            'po_id' => $po_id
        ]);
        
        $data = $response->json();
        $pdf = \PDF::loadView('demo_template', ['data' => $data, 'id' => $po_id])
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif']);

        return $pdf->download('purchase_order.pdf');
        // return $pdf->stream('demo_file.pdf');
    }

    public function downloadWord(Request $request)
    {
        $po_id = $request->query('po_id',  0);
    $response = Http::withOptions(['verify' => false])->get('https://laravelapi.knowbuild.com/laravelapi/api/purchase/purchase-orders-details', [
        'po_id' => $po_id
    ]);
    $data = $response->json();

    $content = View::make('demo_template', ['data' => $data, 'id' => $po_id])->render();
    $headers = [
        "Content-type"=>"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "Content-Disposition"=>"attachment;Filename=purchase_order.doc"
    ];
    return Response::make($content, 200, $headers);
    }
}