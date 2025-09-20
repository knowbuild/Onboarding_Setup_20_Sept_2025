<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Order</title>
</head>
<body style="margin: 0; padding: 32px 16px; background-color: rgb(249, 250, 251); font-family: system-ui, -apple-system, sans-serif;">
  <div style="max-width: 1024px; margin: 0 auto; background-color: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
    <!-- Header -->
    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <img src="https://www.knowbuild.com/images/crm-img/logo.png" alt="Company Logo" style="height: 48px; width: auto;">
        <h1 style="font-size: 24px; font-weight: bold; color: #111827; margin: 0;">Delivery Order</h1>
      </div>
      <div style="margin-top: 16px; display: flex; justify-content: space-between; font-size: 14px;">
        <div>
          <p style="margin: 0;">Date:  {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        </div>
        <div style="text-align: right;">
        <p style="margin: 0;">DO number: {{ $orderNO }}</p>
        <p style="margin: 0;">PO no: {{ $DeliveryOrder->PO_NO }}</p>
        </div>
      </div>
    </div>

    <!-- Buyer and Consignee Details -->
    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <tr>
          <td width="50%" style="padding-right: 12px; vertical-align: top;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">Buyer details</h2>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Company:</span> {{ $DeliveryOrder->Cus_Com_Name }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Buyer:</span> {{ $DeliveryOrder->Buyer_Name }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Address:</span> {{ $DeliveryOrder->Buyer }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Pin Code:</span> {{ $DeliveryOrder->buyer_pincode }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Email:</span> {{ $DeliveryOrder->Buyer_Email }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Telephone:</span> {{ $DeliveryOrder->Buyer_Tel }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Mobile:</span> {{ $DeliveryOrder->Buyer_Mobile }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">GSTIN/UIN:</span> {{ $DeliveryOrder->Buyer_CST }}</p>
            <p style="margin: 0;"><span style="font-weight: 500;">Fax no.:</span> {{ $DeliveryOrder->Buyer_Fax }}</p>
          </td>
          <td width="50%" style="padding-left: 12px; vertical-align: top;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">Consignee details</h2>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Company:</span> {{ $DeliveryOrder->Con_Com_Name }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Consignee:</span> {{ $DeliveryOrder->Con_Name }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Address:</span> {{ $DeliveryOrder->Consignee }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Pin Code:</span> {{ $DeliveryOrder->con_pincode }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Email:</span> {{ $DeliveryOrder->Con_Email }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Telephone:</span> {{ $DeliveryOrder->Con_Tel }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Mobile:</span> {{ $DeliveryOrder->Con_Mobile }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">GSTIN/UIN:</span> {{ $DeliveryOrder->Con_CST }}</p>
            <p style="margin: 0;"><span style="font-weight: 500;">Fax no.:</span> {{ $DeliveryOrder->Con_Fax }}</p>
          </td>
        </tr>
      </table>
    </div>

    <!-- Order Information -->
    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <tr>
        <td width="50%" style="padding-right: 12px; vertical-align: top;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">Purchase Order Information</h2>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Purchase order number:</span> {{ $DeliveryOrder->PO_NO }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">PO creation date:</span> {{ \Carbon\Carbon::parse($DeliveryOrder->PO_Date)->format('d/m/Y') }}</p>
            <p style="margin: 0;"><span style="font-weight: 500;">Payment terms:</span> {{ $DeliveryOrder->paymentTerms->supply_order_payment_terms_name ?? 'N/A' }}</p>
          </td>
          <td width="50%" style="padding-left: 12px; vertical-align: top;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">Price & Warranty details</h2>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Tax %:</span> {{ $DeliveryOrder->Tax_Per }}</p>
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Price inclusive of taxes:</span> <?php echo ($DeliveryOrder->Tax_Stat == 'Extra') ? 'Yes' : 'No';?> </p>
            <p style="margin: 0;"><span style="font-weight: 500;">Warranty:</span> {{ $DeliveryOrder->warrantyMaster->warranty_name }} </p>
          </td>
        </tr>
      </table>
    </div>

    <!-- Product Information -->
    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
      <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 16px 0;">Product information</h2>
      <div style="overflow-x: auto;">
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; min-width: 800px;">
          <thead>
            <tr style="background-color: #f9fafb;">
              <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">S.NO</th>
              <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">ITEM CODE</th>
              <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">DESCRIPTION</th>
              <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">QUANTITY</th>
              <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">UNIT PRICE</th>
              <th style="padding: 12px; text-align: left; font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">SPECIAL INSTRUCTIONS</th>
            </tr>
          </thead>
          <tbody>
          @foreach ($DoProduct as $product)
          <tr style="border-bottom: 1px solid #e5e7eb;">
              <td style="padding: 16px 12px; font-size: 14px; color: #6b7280;">1</td>
              <td style="padding: 16px 12px; font-size: 14px; color: #6b7280;">{{ $product->ItemCode }}</td>
              <td style="padding: 16px 12px; font-size: 14px; color: #6b7280;">{{ $product->Description }}</td>
              <td style="padding: 16px 12px; font-size: 14px; color: #6b7280;">{{ $product->Quantity }}</td>
              <td style="padding: 16px 12px; font-size: 14px; color: #6b7280;">₹ {{ $product->Price }}</td>
              <td style="padding: 16px 12px; font-size: 14px; color: #6b7280;">-</td>
            </tr>
          @endforeach
          
          </tbody>
        </table>
      </div>
    </div>

    <!-- Freight Instructions -->
    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
      <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">Freight Instructions</h2>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <tr>
          <td width="50%" style="padding-right: 12px; vertical-align: top;">
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Mode of Dispatch:</span>{{ $DeliveryOrder->modeMaster->mode_name ?? 'N/A' }}</p>
            <p style="margin: 0;"><span style="font-weight: 500;">Dispatch date:</span>           
@php
    use Carbon\Carbon;

    $orderDate = Carbon::parse($DeliveryOrder->D_Order_Date1);

    switch ($DeliveryOrder->Dispatch) {
        case 'Immediately':
            $dispatchDate = $orderDate;
            break;
        case '1 Day':
            $dispatchDate = $orderDate->addDay();
            break;
        case '1 Week':
            $dispatchDate = $orderDate->addWeek();
            break;
        case '2 Week':
            $dispatchDate = $orderDate->addWeeks(2);
            break;
        case '1 Month':
            $dispatchDate = $orderDate->addMonth();
            break;
        case '2 Months':
            $dispatchDate = $orderDate->addMonths(2);
            break;
        default:
            $dispatchDate = $orderDate;
            break;
    }
@endphp

{{ $dispatchDate->format('d/m/Y') }}</p>
          </td>
          <td width="50%" style="padding-left: 12px; vertical-align: top;">
            <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Freight:</span>{{ $DeliveryOrder->Freight}} </p>
@if ($DeliveryOrder->Freight_amount != 0)
   <p style="margin: 0 0 8px 0;"><span style="font-weight: 500;">Freight Amount:</span>{{ $DeliveryOrder->Freight_amount}} </p>
@endif
            <p style="margin: 0;"><span style="font-weight: 500;">Insurance:</span>{{ $DeliveryOrder->Insurance}} </p>
          </td>
        </tr>
      </table>
    </div>

    <!-- Notes -->
    <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
      <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">Notes</h2>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <tr>
          <td width="50%" style="padding-right: 12px; vertical-align: top;">
            <p style="margin: 0;"><span style="font-weight: 500;">Special Instructions:</span> - {{ $DeliveryOrder->Special_Ins}}</p>
          </td>
          <td width="50%" style="padding-left: 12px; vertical-align: top;">
            <p style="margin: 0;"><span style="font-weight: 500;">Special Invoicing Instructions:</span> - {{ $DeliveryOrder->special_invoicing_ins}}</p>
          </td>
        </tr>
      </table>
    </div>

    <!-- Footer -->
    <div style="padding: 24px; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <p style="margin: 0;"><span style="font-weight: 500;">Issued by:</span>  {{ $DeliveryOrder->accountManager->admin_fname ?? '' }} {{ $DeliveryOrder->accountManager->admin_lname ?? '' }}</p>
      </div>
      <div style="text-align: right;">
        <p style="font-weight: 500; margin: 0 0 8px 0;">Signature</p>
        <div style="height: 64px; width: 128px; border-bottom: 1px solid #9ca3af;"></div>
      </div>
    </div>
  </div>
</body>
</html>