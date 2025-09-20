<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <meta name="description" content="Purchase Order PDF Template">
    <meta name="keywords" content="purchase order, pdf, template">
    
    <style>
        @font-face {
        font-family: 'Noto Sans';
        src: url('{{ storage_path('fonts/NotoSans-Regular.ttf') }}') format('truetype');
        }

        @page {
            size: auto;
            margin: 10px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                margin: 0;
                padding: 0;
                font-family: Inter, sans-serif;
            }
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
        }

        table {
            border: 1px solid #E8E8E8;
            font-size: 11px;
        }

        table tr th {
            border: 1px solid #E8E8E8;
            font-family: Inter, sans-serif;
            font-size: 11px;
            padding: 5px;
            font-weight: normal;
            vertical-align: top;
        }

        table tr.nobor th {
            border-bottom: 0px;
            border-top: 0px;
        }

        table tr.nobor_top th {
            border-bottom: 0px;
        }

        table tr.nobor_bot th {
            border-top: 0px;
        }

        pre {
            font-family: Inter, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        

        .inputton, .inputton_green {
            font-family: Inter, sans-serif;
            font-size: 16px;
            font-weight: bold;
            border: double 5px #efefef;
            padding: 3px 20px;
            color: #ffffff;
            cursor: pointer;
        }

        .inputton {
            background-color: #ed3237;
        }

        .inputton:hover {
            background-color: #ca4c00;
            color: #000;
        }

        .inputton_green {
            background-color: #28a745;
        }

        .inputton_green:hover {
            background-color: #218838;
        }

        .po_payment_follow_up {
            border: none;
            border-top: solid 2px #000;
            font-size: 12px;
            padding: 0;
            margin: 0;
            border-collapse: collapse;
        }

        .btn {
            font-size: 1.3rem;
        }

    /* Removed .break-before and .no-break classes to prevent page break issues in PDF/print rendering */
    </style>
</head>
<body>
    <!-- Main Table -->
    <table style="width: 100%; margin: auto; border: 0;">
        <tbody>
            <tr>
                <td style="vertical-align: top;">
                    <!-- Header Table -->
                    <table style="width: 100%; margin: auto; border: 0;">
                        <tbody>
                            <tr>
                                <td>
                                     <img src="{{ $logoBase64 }}"  alt="Logo">

                                </td>
                                <td style="font-size: 34px; font-weight: 700; background: #fff; text-align: right; padding: 0px; font-family: Inter, sans-serif; color: #1b1b1b;">
                                    Purchase Order
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>

            <!-- Spacer Row -->
            <tr>
                <td style="height: 20px;"></td>
            </tr>

            <!-- Section 2: Order Number and Date -->
            
            <table style="width: 100%; border: 0; margin: 0 0 8px;">
                <tr>
                    <td style="width: 100%; vertical-align: top; border: 1px solid #E8E8E8; border-radius: 8px;">
                        
                            <table style="width: 100%; margin: auto; border: 0;">
                            <tbody>
                                
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: auto; font-size: 16px; ">
                                         Order No
                                        
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: auto; font-size: 16px; ">
                                         {{ $id }}
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: auto; font-size: 16px; ">
                                         Dated
                                        
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #ffffffff; width: auto; font-size: 16px; ">
                                        {{ isset($data['poDetails']['PoCreatedDate']) ? \Carbon\Carbon::parse($data['poDetails']['PoCreatedDate'])->format('d M, Y') : '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    
                    
                </tr>
            </table>
            

            <!-- Section 3: Buyer and Vendor Details -->
            <table style="width: 100%; border: 0;">
                <tr>
                    <td style="width: 49%; vertical-align: top; border: 1px solid #E8E8E8; border-radius: 8px;">
                        <!-- Buyer Details table here start-->
                            <table style="width: 100%; margin: auto; border: 0;">
                                <tbody>
                                    <tr>
                                        <td colspan="2" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                            Buyer Details
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; width: 40%;">
                                            Company Name:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px; width: 60%;">
                                            {{ $data['buyerDetails']['companyName'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Name:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['buyerDetails']['contactName'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Address:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['buyerDetails']['address'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Telephone Number:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['buyerDetails']['telephone'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Mobile Number:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['buyerDetails']['mobile'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Email:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['buyerDetails']['email'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 5px 5px;">
                                            GSTIN/UIN:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; padding: 8px 4px;">
                                            {{ $data['buyerDetails']['gstNo'] ?? '' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        
                        <!-- Buyer Details table here end-->
                    </td>
                    <td style="width: 2%;"></td>
                    <td style="width: 49%; vertical-align: top; border: 1px solid #E8E8E8; border-radius: 8px;">
                        <!-- Vendor Details table here start-->
                         <table style="width: 100%; margin: auto; border: 0;">
                                <tbody>
                                    <tr>
                                        <td colspan="2" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                            Vendor Details
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; width: 40%;">
                                            Company Name:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px; width: 60%;">
                                            {{ $data['vendorDetails']['companyName'] ?? '' }}

                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Name:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['vendorDetails']['contactName'] ?? '' }}

                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Address:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['vendorDetails']['address'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Telephone Number:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['vendorDetails']['telephone'] ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Mobile Number:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['vendorDetails']['mobile'] ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Email:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; border-bottom: 1px solid #E8E8E8; padding: 8px 4px;">
                                            {{ $data['vendorDetails']['email'] ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 5px 5px;">
                                            GSTIN/UIN:
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; font-weight: 400; padding: 8px 4px;">
                                            {{ $data['vendorDetails']['gstNo'] ?? '' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <!-- Vendor Details table here end-->
                    </td>
                </tr>
            </table>
            

            <!-- Section 4: Consignee Details -->
            

                <tr>
                    <td style="font-family: Inter, sans-serif; vertical-align: top;">
                        <div style="width: 100%; border: 1px solid #E8E8E8; border-radius: 8px; margin: 20px 0 20px;">
                            <table style="width: 100%; margin: auto; border: 0;">
                                <tbody>
                                    <tr>
                                        <td colspan="2" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                            Consignee Details
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;  width: 50%;">
                                            Company Name: 
                                            <span style="font-weight: 400; color: #484848;">
                                                 {{ $data['consigneeDetails']['companyName'] ?? '' }}
                                            </span>
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            Telephone Number: 
                                            <span style="font-weight: 400; color: #484848;">
                                                 {{ $data['consigneeDetails']['telephone'] ?? '' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            Mobile Number: 
                                            <span style="font-weight: 400; color: #484848;">
                                                     {{ $data['consigneeDetails']['mobile'] ?? '' }}        
                                            </span>
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; width: 50%;">
                                            Consignee Contact Name: 
                                            <span style="font-weight: 400; color: #484848;">
                                                           {{ $data['consigneeDetails']['contactName'] ?? '' }}

                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 5px 5px; width: 50%;">
                                            Address: 
                                            <span style="font-weight: 400; color: #484848;">
                                                        {{ $data['consigneeDetails']['address'] ?? '' }}
                                                <br>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            </tbody>
    </table>

            <!-- Section 5: Items Ordered -->
            {{-- <tr>
                <td style="font-family: Inter, sans-serif; vertical-align: top;">
                    <div style="width: 100%; border: 1px solid #E8E8E8; border-radius: 8px; margin: 0 0 20px;"> --}}
                        <table style="width: 100%; margin: 0 0 20px; border: 0; ">
                            <tbody>
                                <tr>
                                    <td colspan="8" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                        Items Ordered
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 5%;">
                                        S.No.
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 30%;">
                                        Product/Purchase Description
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 12%;">
                                        UPC
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 12%;">
                                        HSN
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 12%;">
                                        Vendor Item Code
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 5%;">
                                        Qty.
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8; width: 12%; text-align: right;">
                                        Product Price
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; width: 12%; text-align: right;">
                                        Total Price
                                    </td>
                                </tr>
                                <!-- Item All -->
                                @foreach ($data['requirementDetails']['requirements'] ?? [] as $index => $item)
                                    <tr>
                                        <td style="font-family: Inter, sans-serif; color: #484848; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            {{ $item['sno'] ?? $index+1 }}
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            {{ $item['description'] ?? '' }}
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            {{ $item['upc'] ?? '' }}
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            {{ $item['hsn'] ?? '' }}
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            {{ $item['vendorItemCode'] ?? '' }}
                                        </td>
                                        <td style="font-family: Inter, sans-serif; color: #484848; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                            {{ $item['quantity'] ?? '' }}
                                        </td>
                                        <td style="font-family: 'DejaVu Sans', 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; border-bottom: 1px solid #E8E8E8; padding: 5px 5px; border-right: 1px solid #E8E8E8;">
                                        {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!} {{ number_format($item['productPrice'] ?? 0, 2) }}
                                        </td>
                                        <td style="font-family: 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; border-bottom: 1px solid #E8E8E8; padding: 5px 5px;">
                                            {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!} {{ number_format($item['subTotal'] ?? 0, 2) }}
                                        </td>
                                    </tr>
                                @endforeach

                               
                                <!-- Sub Total -->
                                <tr>
                                    <td colspan="4" style="border-right: 1px solid #E8E8E8; border-bottom: 1px solid #E8E8E8;">
                                        &nbsp;
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; text-align: right; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px; border-right: 1px solid #E8E8E8;" colspan="2">
                                        Sub Total
                                    </td>
                                    <td style="font-family: 'DejaVu Sans', 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; border-bottom: 1px solid #E8E8E8; padding: 5px;" colspan="2">
                                        {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!} 
                                        {{ number_format($data['requirementDetails']['subTotal'] ?? 0, 2) }}
                                        </td>
                                    </td>
                                </tr>
                                <!-- CGST -->
                                <tr>
                                    <td colspan="4" style="border-right: 1px solid #E8E8E8; border-bottom: 1px solid #E8E8E8;">
                                        &nbsp;
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; text-align: right; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px; border-right: 1px solid #E8E8E8;" colspan="2">
                                        CGST (9%)
                                    </td>
                                    <td style="font-family: 'DejaVu Sans', 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; border-bottom: 1px solid #E8E8E8; padding: 5px;" colspan="2">
                                         {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!}
                                         {{ number_format($data['requirementDetails']['cgst'] ?? 0, 2) }}
                                        </td>
                                    </td>
                                </tr>
                                <!-- SGST -->
                                <tr>
                                    <td colspan="4" style="border-right: 1px solid #E8E8E8; border-bottom: 1px solid #E8E8E8;">
                                        &nbsp;
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; text-align: right; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px; border-right: 1px solid #E8E8E8;" colspan="2">
                                        SGST (9%)
                                    </td>
                                    <td style="font-family: 'DejaVu Sans', 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; border-bottom: 1px solid #E8E8E8; padding: 5px;" colspan="2">
                                          {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!}
                                          {{ number_format($data['requirementDetails']['sgst'] ?? 0, 2) }}
                                        </td>
                                    </td>
                                </tr>
                                <!-- PO Total -->
                                <tr>
                                    <td colspan="4" style="border-right: 1px solid #E8E8E8; border-bottom: 1px solid #E8E8E8;">
                                        &nbsp;
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; text-align: right; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px; border-right: 1px solid #E8E8E8;" colspan="2">
                                        PO Total
                                    </td>
                                    <td style="font-family: 'DejaVu Sans', 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; border-bottom: 1px solid #E8E8E8; padding: 5px;" colspan="2">
                                          {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!}
                                          {{ number_format($data['requirementDetails']['poTotal'] ?? 0, 2) }}
                                        </td>
                                    </td>
                                </tr>
                                <!-- Amount Chargeable -->
                                <tr>
                                    <td colspan="3" style="border-right: 1px solid #E8E8E8; border-bottom: 1px solid #E8E8E8;">
                                        &nbsp;
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; text-align: right; font-weight: 700; padding: 5px; border-bottom: 1px solid #E8E8E8; border-right: 1px solid #E8E8E8;" colspan="3">
                                        Amount Chargeable (INR)
                                    </td>
                                    <td style="font-family: 'DejaVu Sans', 'Noto Sans', Arial, sans-serif; color: #484848; text-align: right; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 5px;" colspan="2">
                                          {!! $data['poDetails']['currency_css_symbol'] ?? '&#8377;' !!}
                                          {{ number_format($data['requirementDetails']['poTotal'] ?? 0, 2) }}/-
                                    </td>
                                </tr>
                                <!-- Amount in Words -->
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 5px;" colspan="8">
                                        Amount Chargeable (INR) : 
                                        <b style="font-weight: bold; text-transform: capitalize;">
                                            {{ $data['requirementDetails']['amt_in_words'] ?? '' }}
                                        </b>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    {{-- </div>
                </td>
            </tr> --}}

            <!-- Section 6: Dispatch Details -->
            <table style="width: 100%; margin: auto; border: 0;">
            <tbody>
            <tr>
                <td style="font-family: Inter, sans-serif; vertical-align: top;">
                    <div style="width: 100%; border: 1px solid #E8E8E8; border-radius: 8px; margin: 0 0 20px;">
                        <table style="width: 100%; margin: auto; border: 0;">
                            <tbody>
                                <tr>
                                    <td colspan="2" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                        Dispatch Details
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: 50%;">
                                        Dispatch Required By: 
                                        <span style="font-weight: 400; color: #484848;">
                                           
                                            {{ isset($data['dispatchDetails']['dispatchDate']) ? \Carbon\Carbon::parse($data['dispatchDetails']['dispatchDate'])->format('d M, Y') : '' }}
                                        </span>
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px;">
                                        Mode of Dispatch: 
                                        <span style="font-weight: 400; color: #484848;">
                                           {{ $data['dispatchDetails']['mode_name'] ?? '' }}
                                           
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>

            <!-- Section 7: Terms & Conditions -->
            <tr>
                <td style="font-family: Inter, sans-serif; vertical-align: top;">
                    <div style="width: 100%; border: 1px solid #E8E8E8; border-radius: 8px; margin: 0 0 20px;">
                        <table style="width: 100%; margin: auto; border: 0;">
                            <tbody>
                                <tr>
                                    <td colspan="2" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                        Terms & Conditions
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: 50%;">
                                        Payment Terms: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['termsDetails']['paymentTermsName'] ?? '' }}
                                        </span>
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 8px 5px;">
                                        Price Basis: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['termsDetails']['priceBasis_name'] ?? '' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 8px 5px; border-right: 1px solid #E8E8E8;">
                                        Warranty: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['termsDetails']['warranty_name'] ?? '' }}
                                        </span>
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 8px 5px;">
                                        Additional Instruction: 
                                        <span style="font-weight: 400; color: #484848;">
                                           {{ $data['termsDetails']['additionalInstructions'] ?? '' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #E8E8E8;">
                                        Order Type: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ ucfirst($data['termsDetails']['purchase_type'] ?? '') }}
                                        </span>
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px;">
                                        Purchase Currency: 
                                        <span style="font-weight: 400; color: #484848;">
                                          ({{ $data['poDetails']['Currency'] ?? '' }})
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>

            <!-- Section 8: Payment Follow Up -->
            <tr>
                <td style="font-family: Inter, sans-serif; vertical-align: top;">
                    <div style="width: 100%; border: 1px solid #E8E8E8; border-radius: 8px; margin: 0 0 20px;">
                        <table style="width: 100%; margin: auto; border: 0;">
                            <tbody>
                                <tr>
                                    <td colspan="2" style="background: #F9F9FA; padding: 5px 5px; font-family: Inter, sans-serif; color: #1b1b1b; font-size: 16px; font-weight: 700; border-bottom: 1px solid #E8E8E8;">
                                        For Payment Follow Up
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: 50%;">
                                        Name : 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['paymentFollowUpDetails']['contactName'] ?? '' }}
                                        </span>
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; border-bottom: 1px solid #E8E8E8; padding: 8px 5px;">
                                        Phone Number: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['paymentFollowUpDetails']['telephone'] ?? '' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px; border-right: 1px solid #E8E8E8; width: 50%;">
                                        Email: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['paymentFollowUpDetails']['email'] ?? '' }}
                                        </span>
                                    </td>
                                    <td style="font-family: Inter, sans-serif; color: #1b1b1b; font-weight: 700; padding: 8px 5px;">
                                        CC: 
                                        <span style="font-weight: 400; color: #484848;">
                                            {{ $data['paymentFollowUpDetails']['cc'] ?? '' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>