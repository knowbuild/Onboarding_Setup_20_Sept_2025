<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Validator;

class SystemConfigurationController extends Controller
{
    public function index()
    {
        $configs = SystemConfiguration::where('status', 'active')
            ->orderBy('header_title')
            ->orderBy('row_no')
            ->get()
            ->groupBy('header_title');

        $data = [];

        foreach ($configs as $headerTitle => $items) {
            $first = $items->first();

            $card = [
                'header' => [
                    'title' => $first->header_title,
                    'backgroundColor' => $first->header_color,
   'icon' => asset('material/system-configuration/' . $first->header_icon),
                                      'headerIconBackground' => $first->header_icon_background,
                ],
                'content' => [
                    'conRow1' => $items->where('row_no', 1)->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'footerText' => $item->footer_text,
                            'link' => $item->link,
                        ];
                    })->values(),
                    'conRow2' => $items->where('row_no', 2)->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'footerText' => $item->footer_text,
                            'link' => $item->link,
                        ];
                    })->values(),
                ]
            ];

            $data[] = $card;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'System Configuration retrieved successfully.',
            'data' => $data,
        ], 200);
    }
}
 