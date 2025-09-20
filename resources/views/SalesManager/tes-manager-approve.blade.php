<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>TES Approval</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; padding: 32px;">

  <div style="max-width: 850px; margin: auto; background-color: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">

    <!-- Header -->
    <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 24px;">
      <img src="https://www.knowbuild.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2Fknowbuild-logo.bb495062.png&w=256&q=75" alt="Logo" style="height: 48px;">
      <h2 style="margin-top: 16px; font-size: 24px; color: #1f2937;">TES Manager Approval</h2>
      <p style="margin: 4px 0; color: #4b5563;"><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
      <p style="margin: 4px 0; color: #4b5563;"><strong>Account Manager:</strong> {{ $accountManagerName }}</p>
      <p style="margin: 4px 0; color: #4b5563;"><strong>Financial Year:</strong> {{ $financialYearName }}</p>
      <p style="margin: 4px 0; color: #4b5563;"><strong>TES Target:</strong> {{ number_format($tesManager->tes_target ?? 0, 2) }}</p>
      <p style="margin: 4px 0; color: #4b5563;"><strong>Discount:</strong> {{ number_format($tesManager->discount ?? 0, 2) }}</p>
      <p style="margin: 4px 0; color: #4b5563;"><strong>Actual Target:</strong> {{ number_format($tesManager->actual_target ?? 0, 2) }}</p>
    </div>

    <!-- Product Targets -->
    <div>
      <h3 style="font-size: 20px; margin-bottom: 16px; color: #111827;"> Product Targets</h3>

      @foreach ($tesData as $company)
        <div style="margin-bottom: 16px; background-color: #f9fafb; padding: 12px 16px; border-left: 4px solid #3b82f6; border-radius: 6px;">
          <strong style="color: #1f2937;">Company:</strong> {{ $company['company_name'] }} ( {{ $company['segment_name'] }})
        
        </div>

        <table width="100%" style="border-collapse: collapse; margin-bottom: 32px;">
          <thead>
            <tr style="background-color: #e0f2fe; text-align: left; color: #0c4a6e;">
              <th style="padding: 10px; font-size: 14px;">S.No</th>
              <th style="padding: 10px; font-size: 14px;">Product</th>
              <th style="padding: 10px; font-size: 14px;">Quantity</th>
              <th style="padding: 10px; font-size: 14px;">Price</th>
              <th style="padding: 10px; font-size: 14px;">Discount</th>
              <th style="padding: 10px; font-size: 14px;">Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($company['items'] as $index => $item)
              <tr style="border-bottom: 1px solid #e5e7eb; background-color: {{ $index % 2 == 0 ? '#ffffff' : '#f9fafb' }};">
                <td style="padding: 10px;">{{ $index + 1 }}</td>
                <td style="padding: 10px;">{{ $item['product_name'] }}</td>
                <td style="padding: 10px;">{{ $item['quantity'] }}</td>
                <td style="padding: 10px;">{{ number_format($item['price'], 2) }}</td>
                <td style="padding: 10px;">{{ number_format($item['discount'], 2) }}</td>
                <td style="padding: 10px;">{{ number_format($item['total_price'], 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endforeach
    </div>
    
  </div>
</body>
</html>
