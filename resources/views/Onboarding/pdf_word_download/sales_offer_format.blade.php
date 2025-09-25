<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Offer Template</title>
    <style>
        @page {
            margin: 10px;
        }
        body {
            margin: 0;
            padding: 0;
            font-size: 12px;
            font-family: Arial, sans-serif;
            background: #ffffff;
        }
        img {
            display: block;
            max-width: 100%;
            height: auto;
            -ms-interpolation-mode: bicubic;
            image-rendering: optimizeQuality;
        }
        .details-box {
            background: #F3F5FA;
            border-radius: 8px;
            margin: 20px 0;
        }
        .details-grid {
            display: table;
            width: 100%;
        }
        .details-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 16px;
        }
        .details-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            border-left: 1px solid #E8E8E8;
        }
        .info-row {
            border-bottom: 1px solid #E8E8E8;
            display: table;
            width: 100%;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            color: #767676;
            line-height: 20px;
            padding: 7px 10px 7px 16px;
            text-transform: uppercase;
            font-size: 11px;
        }
        .info-value {
            display: table-cell;
            width: 60%;
            color: #1B1B1B;
            line-height: 20px;
            padding: 7px 10px 7px 16px;
            font-size: 11px;
        }
        .subject-line {
            padding: 16px 0 24px;
        }
    </style>
</head>
<body>
    <div style="width: 98%; margin: 0 auto;">
        <table cellpadding="0" cellspacing="0" width="100%" border="0">
            <tr>
                <td style="padding: 0 0 20px;">
                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tr>
                            <td style="width: 50%;">
                                <table cellpadding="0" cellspacing="0" width="100%" border="0">
                                    <tr>
                                        <td><img src="{{ public_path($saleOfferTemplate->company_logo) }}" style="max-height: 80px;"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p style="margin: 0; padding: 0;line-height: 20px;"><b>{{ $saleOfferTemplate->organisation }}</b></p>
                                            <p style="margin: 0; padding: 0;line-height: 20px;">{{ $saleOfferTemplate->address }}</p>
                                            <p style="margin: 0; padding: 0;line-height: 20px;">Tel: {{ $saleOfferTemplate->tel }}</p>
                                            <p style="margin: 0; padding: 0;line-height: 20px;">Email: {{ $saleOfferTemplate->email }}</p>
                                            <p style="margin: 0; padding: 0;line-height: 20px;">Web: {{ $saleOfferTemplate->company_website }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="width: 50%; text-align: right; vertical-align: middle;">
                                <img src="{{ public_path($saleOfferTemplate->company_logo) }}" style="max-height: 80px; display: block; margin: 0 0 10px auto;">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Customer Details Section -->
            <tr>
                <td>
                    <div style="background: #F3F5FA; border-radius: 8px;">
                        <table cellpadding="0" cellspacing="0" width="100%" border="0">
                            <tr>
                                <td style="width: 50%; vertical-align:top; padding: 16px;">
                                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                                        <tr>
                                            <td>
                                                <p style="font-weight: 600;margin: 0; padding: 0;line-height: 15px;color: #1B1B1B;font-size: 11px;">To,</p>
                                                <p style="margin: 0; padding: 0 0 20px;line-height: 18px;color: #1B1B1B;font-size: 11px;">Mr. Rahul Sharma</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <p style="font-weight: 600;margin: 0; padding: 0;line-height: 15px;color: #1B1B1B;font-size: 11px;">Manager</p>
                                                <p style="margin: 0; padding: 0;line-height: 18px;color: #1B1B1B;font-size: 11px;">M/s. Prasad & Company ( Project works)<br>
                                                    # 6-3-871, Snehalata,Greenlands Road Begumpet, Telangana, Hyderabad,</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 50%; border-left: 1px solid #E8E8E8; vertical-align: top;">
                                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                                        <tr>
                                            <td style="width: 40%; color: #767676;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8; text-transform: uppercase; font-size: 11px;">EID</td>
                                            <td style="width: 60%;color: #1B1B1B;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;font-size: 11px;">24P3394</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 40%; color: #767676;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;text-transform: uppercase;font-size: 11px;">Offer Reference</td>
                                            <td style="width: 60%;color: #1B1B1B;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;font-size: 11px;">S2ACULG-37745</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 40%; color: #767676;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;text-transform: uppercase;font-size: 11px;">Date</td>
                                            <td style="width: 60%;color: #1B1B1B;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;font-size: 11px;">11 Aug, 2025</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 40%; color: #767676;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;text-transform: uppercase;font-size: 11px;">Mobile</td>
                                            <td style="width: 60%;color: #1B1B1B;line-height: 20px; padding: 7px 10px 7px 16px;border-bottom: 1px solid #E8E8E8;font-size: 11px;">82877778999</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 40%; color: #767676;line-height: 20px; padding: 7px 10px 7px 16px;text-transform: uppercase;font-size: 11px;">E-mail</td>
                                            <td style="width: 60%;color: #1B1B1B;line-height: 20px; padding: 7px 10px 7px 16px;font-size: 11px;">24P3394</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>

            <!-- Subject Line -->
            <tr>
                <td style="padding: 16px 0 24px">
                    <div style="font-size: 14px; line-height: 20px;">
                        
                         {!! $saleOfferTemplate->subject !!}
                    </div>
                </td>
            </tr>

            <!-- Dear Sir/Madam Section -->
            <tr>
                <td style="padding: 0 0 15px; margin: 0;">
                    {!! $saleOfferTemplate->mail_message !!}
                </td>
            </tr>

            <!-- Product Table -->
            <tr>
                <td style="vertical-align: top;">
                    <div style="border: 1px solid #E8E8E8; border-radius: 8px;">
                        <table cellpadding="0" cellspacing="0" width="100%" border="0">
                            <thead>
                                <tr>
                                    <th style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;font-size: 12px;font-weight: 700; padding: 8px;">S.no.</th>
                                    <th style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: left;font-size: 12px;font-weight: 700; padding: 8px;">Product name</th>
                                    <th style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: left;font-size: 12px;font-weight: 700; padding: 8px;">Description</th>
                                    <th style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: center;font-size: 12px;font-weight: 700; padding: 8px;">Qty A</th>
                                    <th style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: center;font-size: 12px;font-weight: 700; padding: 8px;">Unit price B</th>
                                    <th style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: center;font-size: 12px;font-weight: 700; padding: 8px;">Add IGST value D</th>
                                    <th style="border-bottom: 1px solid #E8E8E8;text-align: center;font-size: 12px;font-weight: 700; padding: 8px;">Sub total = A * B</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8; padding: 8px;">1</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;padding: 8px; font-weight: 600;">
                                        <p style="padding: 0; margin: 0;line-height: 16px;">12W Pathfinder Precision Pipe & Cable Locator</p>
                                        <p style="padding: 0; margin: 0;line-height: 16px;">UPC : <span style="font-weight: 400;">103672</span></p>
                                        <p style="padding: 0; margin: 0;line-height: 16px;">HSN : <span style="font-weight: 400;">90318000</span></p>
                                        <p style="padding: 0; margin: 0;line-height: 16px;">Part No : <span style="font-weight: 400;">STLOC10-MC</span></p>
                                    </td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;padding: 8px;">Pathfinder High Power Pipe and Cable locating kit with 36 user configurable Active multi frequency options including 200Hz, 512Hz, 797Hz</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;padding: 8px;text-align: center;">1</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: right;padding: 8px;white-space: nowrap;">₹ 12,09,000.00</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: right;padding: 8px;white-space: nowrap;">₹ 2,17,620.00<br>(18%)</td>
                                    <td style="border-bottom: 1px solid #E8E8E8;text-align: right;padding: 8px;white-space: nowrap;">₹ 12,09,000.00</td>
                                </tr>
                                <tr>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8; padding: 8px;">2</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;padding: 8px; font-weight: 600;">
                                        <p style="padding: 0; margin: 0;line-height: 16px;">12W Pathfinder Precision Pipe & Cable Locator</p>
                                        <p style="padding: 0; margin: 0;line-height: 16px;">UPC : <span style="font-weight: 400;">103672</span></p>
                                        <p style="padding: 0; margin: 0;line-height: 16px;">HSN : <span style="font-weight: 400;">90318000</span></p>
                                        <p style="padding: 0; margin: 0;line-height: 16px;">Part No : <span style="font-weight: 400;">STLOC10-MC</span></p>
                                    </td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;padding: 8px;">Pathfinder High Power Pipe and Cable locating kit with 36 user configurable Active multi frequency options including 200Hz, 512Hz, 797Hz</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;padding: 8px;text-align: center;">2</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: right;padding: 8px;white-space: nowrap;">₹ 12,09,000.00</td>
                                    <td style="border-right: 1px solid #E8E8E8;border-bottom: 1px solid #E8E8E8;text-align: right;padding: 8px;white-space: nowrap;">₹ 2,17,620.00<br>(18%)</td>
                                    <td style="border-bottom: 1px solid #E8E8E8;text-align: right;padding: 8px;white-space: nowrap;">₹ 12,09,000.00</td>
                                </tr>

                                <!-- Totals Section -->
                                <tr>
                                    <td style="text-align: right;padding: 8px;font-weight: 600;padding: 8px; border-bottom: 1px dotted #E8E8E8;" colspan="5">Sub Total :</td>
                                    <td style="text-align: right;padding: 8px;border-bottom: 1px dotted #E8E8E8;white-space: nowrap;" colspan="2">₹ 12,61,000.00</td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;padding: 8px;font-weight: 600;padding: 8px; border-bottom: 1px dotted #E8E8E8;" colspan="5">Freight Value :</td>
                                    <td style="text-align: right;padding: 8px;border-bottom: 1px dotted #E8E8E8;white-space: nowrap;" colspan="2">₹ 1,500.00</td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;padding: 8px;font-weight: 600;padding: 8px; border-bottom: 1px dotted #E8E8E8;" colspan="5">GST :</td>
                                    <td style="text-align: right;padding: 8px;border-bottom: 1px dotted #E8E8E8;white-space: nowrap;" colspan="2">₹ 227250.00</td>
                                </tr>
                                <tr>
                                    <td style="text-align: right;padding: 8px;font-weight: 600;padding: 8px;" colspan="5">Grand Total :</td>
                                    <td style="text-align: right;padding: 8px;font-weight: 600;white-space: nowrap;" colspan="2">₹ 14,89,750.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>

            <!-- ISO Certification Section -->
            <tr>
                <td style="color: #484848;">
                    <p>We are an <strong>ISO 9001:2015</strong> certified quality organization with sales and servicing from 10 locations- <strong>New Delhi, Lucknow, Mumbai, Kolkata, Bangalore, Hyderabad, Bhubaneshwar, Guwahati, Patna, Vadodara & our works at Faridabad</strong> to ensure that our customers receive our best attention on supply, training and service.</p>
                </td>
            </tr>

            <!-- Terms and Conditions Section -->
            <tr>
                <td style="vertical-align: top;color: #484848;">
                    <h5 style="margin: 0; padding: 0 0 10px; font-size: 16px; line-height: 22px;color: #484848;">Terms And Conditions:</h5>
                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                        @for($i = 1; $i <= 6; $i++)
                            @if(isset($saleOfferTemplate["term_name{$i}"]) && $saleOfferTemplate["term_disable{$i}"] === "false")
                                <tr>
                                    <td style="padding: 0 0 5px;"><b>{{ $saleOfferTemplate["term_name{$i}"] }}:</b> {{ $saleOfferTemplate["term_details{$i}"] }}</td>
                                </tr>
                            @endif
                        @endfor
                        <tr>
                            <td style="padding: 0 0 5px;"><b>Order in favour of:</b> Asian Contec Limited, B-28, Okhla Industrial Area, Phase - I, New Delhi - 110020.</td>
                        </tr>
                        <tr>
                            <td style="padding: 0 0 5px;"><b>Delivery:</b> Within 120 Days.</td>
                        </tr>
                        <tr>
                            <td style="padding: 0 0 5px;"><b>Payment:</b> 10% Advance & Balance 90% through LC.</td>
                        </tr>
                        <tr>
                            <td style="padding: 0 0 5px;"><b>Delivery by:</b> Spot on/ GATI/ DTDC as mutually agreed.</td>
                        </tr>
                        <tr>
                            <td style="padding: 0 0 5px;"><b>Validity:</b> Offer is valid for 15 Days from offer date.</td>
                        </tr>
                        <tr>
                            <td style="padding: 0 0 5px;"><b>Warranty:</b> 1 Year</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Notes Section -->
            <tr>
                <td style="vertical-align: top; color: #484848;">
                    <h5 style="margin: 0; padding: 0 0 10px; font-size: 16px; line-height: 22px;color: #484848;">Notes:</h5>
                    <ol style="margin: 0 0 0; padding: 0 0 0 15px; line-height: 16px;">
                        <li>Discount % if mentioned in offer should be utilised only for your internal reference & should not/will not be further mentioned in commercial documents including purchase order, invoice etc.</li>
                        <li>Unless otherwise agreed to, Standard Payments are 100% in Advance, unless any other payment term has been agreed to. Delay of the payment caused by Buyer, may entitle M/s Asian Contec limited to Charge interest @15 % per annum basis.</li>
                        <li>Offer is subject to GENERAL STANDARD TERMS AND CONDITIONS FOR THE SALE OF PRODUCTS, enclosed.</li>
                    </ol>
                    <p>We look forward to receiving your valued order for which we thank you in advance<br><b>Thanking you,</b></p>
                    <p>Your sincerely,<br>For <b>ASIAN CONTEC LIMITED</b></p>

                    <p style="font-weight: 600;">Ankit G<br>Admin Manager</p>

                    <p style="margin: 0 0 0; padding: 0 0 0 0; line-height: 16px;">
                        Mobile: 9811169723<br>
                        Tel No: +91-11-41860000 (100 Lines)<br>
                        E-Mail: sales@stanlay.com, ankit@stanlay.com<br>
                        Web: www.stanlay.in<br>
                        Web: www.stanlay.com
                    </p>
                </td>
            </tr>

            <!-- Contact Information Section -->
            <tr>
                <td>
                    <div style="background: #E6F7F3; border-radius: 8px; margin: 20px 0; padding: 16px; text-align: center;">
                        <p style="color: #03A580; padding: 0; margin: 0 0 8px; font-size: 12px; line-height: 18px;">Thank you for considering purchase from our company. While your point of contact is our sales team member as mentioned above, Other contacts for Coordination who can be of further service pre and post sales are as follows.</p>
                        <p style="color: #03A580; padding: 0; margin: 0; font-size: 12px; line-height: 18px;">(Central contact phone : 011-41860000 ; helpline : 011-41406926)</p>
                    </div>

                    <table cellpadding="0" cellspacing="0" width="100%" border="0" style="border-collapse: separate; border-spacing: 10px;">
                        <tr>
                            <!-- Invoicing & Accounts -->
                            <td width="25%" style="vertical-align: top; background: #f7f7fa; border-radius: 10px; padding: 16px; box-shadow: 0 0 2px #ccc;">
                                {!! $saleOfferTemplate->contact_member1 !!}

                            </td>

                            <!-- Technical queries & Service -->
                            <td width="25%" style="vertical-align: top; background: #f7f7fa; border-radius: 10px; padding: 16px; box-shadow: 0 0 2px #ccc;">
                                 {!! $saleOfferTemplate->contact_member2 !!}
                            </td>

                            <!-- Sales -->
                            <td width="25%" style="vertical-align: top; background: #f7f7fa; border-radius: 10px; padding: 16px; box-shadow: 0 0 2px #ccc;">
                                                                {!! $saleOfferTemplate->contact_member3 !!}

                                
                            </td>

                            <!-- Shipping & Coordination -->
                            <td width="25%" style="vertical-align: top; background: #f7f7fa; border-radius: 10px; padding: 16px; box-shadow: 0 0 2px #ccc;">
                                                                {!! $saleOfferTemplate->contact_member4 !!}

                                
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Terms and Conditions Page -->
            <tr>
                <td style="vertical-align: top;" class="break-before">
                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tr>
                            <td style="vertical-align: top; color: #484848;">
                                <h3 style="margin: 0; padding: 0 0 10px;line-height: 18px; font-size: 16px;font-weight: 600;">GENERAL STANDARD TERMS AND CONDITIONS FOR THE SALE OF PRODUCTS (GCC)</h3>

                                <!-- Section 1 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 5px; display: block;">1. GENERAL</strong>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0;">These General Terms and Conditions (T&C) for the Sale of Products and Services (Products) is by and between Asian Contec Limited, India and its subsidiaries and/or affiliates (Seller), and the party contracting to purchase the Products (Buyer). Buyer represents and warrants to Seller that Buyer has the authority and right to enter into this Agreement without breaching or violating any fiduciary, contractual, statutory, or other legal obligations. Any Seller proposal and acknowledgement of Buyer's purchase order or contract (Order or Contract) are expressly made in accordance with the T&C hereof. If individual provisions of these T&C are in conflict with the provisions set forth in Seller Offer or Order acknowledgement, these latter shall take precedence and the remain provisions of these T&C's, not directly in conflict, shall continue to control.</p>

                                <!-- Section 2 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 5px; display: block;">2. PRICE, DELIVERY AND TITLE</strong>
                                <p style="margin: 0; padding: 0; font-size: 11px; color: #484848;line-height: 15px;">Unless otherwise agreed to in writing by both parties</p>
                                <p style="margin: 0; padding: 0; font-size: 11px; color: #484848;line-height: 15px;">i. Delivery offered is ex-works, unless otherwise specified. Freight changes shall be charged at actuals or as stated in offer.</p>
                                <p style="margin: 0; padding: 0; font-size: 11px; color: #484848;line-height: 15px;">ii. Title to and risk of loss or damage shall pass to Buyer upon handover of goods to transporter who will constitute buyer's agent.</p>
                                <p style="margin: 0; padding: 0; font-size: 11px; color: #484848;line-height: 15px;">iii. Seller will endeavor to meet the delivery schedule as specified in the Order, but in no case Seller will be responsible for delay in deliver at the time specified in the Order especially in the occurrence of unforeseeable events, circumstances or conditions beyond the Seller's control, such as all events of Force Majeure, which prevent to deliver in time; such events shall include in particular Embargoes, Export Restrictions, Armed Conflicts, Transport and Customs Delay, Shipping Damage, Power and Raw Material Shortage, Labour Disputes and Default of a major Supplier. The concerned delivery dates shall be extended for a period equal to the duration of such events.</p>
                                <p style="margin: 0; padding: 0; font-size: 11px; color: #484848;line-height: 15px;">iv. Early delivery or expedited shipping will be possible upon ACL written consent with possible additional fee.</p>
                                <p style="margin: 0; padding: 0 0 5px; font-size: 11px; color: #484848;line-height: 15px;">v. Should an event of Force Majeure lead to the cancellation of a purchase order already accepted by ACL, then ACL maximum aggregate liability shall be the repayment, without interests, of the sums received for the Products or Services not supplied.</p>

                                <!-- Section 3 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 3px; display: block; color: #484848;">3. ACCEPTANCE OF PRODUCTS</strong>
                                <p style="font-size: 12px;line-height: 18px; padding: 0 0 5px; margin: 0; color: #484848;">Seller will supply the Products in accordance with manufacturer's quality control processes and procedures. Buyer's inspection of the Products shall be made at the Buyer's facility and at Buyer's expense. Buyer shall, immediately upon receipt, notify Seller of any damage, discrepancy, or nonconformity, other than quantity, in the Products. Buyer's failure to inspect or failure to timely notify Seller of any damage, discrepancy, or nonconformity shall be deemed a waiver of any and all such claims and shall relieve Seller from its obligations and any liability. Seller's weights and quantity counts taken at the shipping point shall govern. Buyer hereby waives any claims unless Buyer notifies Seller in writing of any such discrepancy within ten (10) calendar days after the date of receipt of products.</p>

                                <!-- Section 4 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 2px; display: block; color: #484848;">4. PAYMENT</strong>
                                <p style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Unless otherwise agreed to,</p>
                                <ol style="margin: 0; padding: 0 0 0 17px;">
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Standard Payments are 100% in Advance from date of Proforma invoice date, but, in any case, before shipment occurs, unless any other payment term has been agreed to.</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Payments shall be made, without deductions and without any set-off.</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Delay of the payment caused by Buyer, may entitle Seller to :</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Delay the fulfillment of its obligations until such payment has been effected;</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Extend the time of delivery accordingly;</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Charge interest @12% per annum basis or 3 times the RBI interest rates, at months rests, whichever is higher.</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">We are an MSME unit and covered by provisions of Section 15 of the MSME Act of Govt of India which states that the payment to the MSME suppliers shall be made within the date specified in the agreement (order) not exceeding 45 days from date of delivery/acceptance beyond which the Act specifies charges at the rate of 3 times the RBI interest rate at monthly rests.</li>
                                    <li style="font-size: 12px;line-height: 16px; padding: 0; margin: 0; color: #484848;">Retain the title of goods delivered until due amounts have been fully received.</li>
                                </ol>

                                <!-- Section 5 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 5px; display: block;">5. WARRANTY</strong>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0; padding-left: 15px;">1. Seller warrants that its Products shall be free from defects in material and workmanship, for a period of 12 months or as per manufacturers policy for product supplied from the dispatch date. Seller's obligation is limited to repairing or replacing parts or products which are returned, without alteration or further damage, and which at ACL or manufacturer's judgment, were defective or became defective during its normal use.</p>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0; padding-left: 15px;">2. For Products returned during the warranty period, shipping costs from Buyer to Seller are borne by Buyer, from Seller to Buyer are borne by Seller.</p>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0; padding-left: 15px;">3. Any different warranty, granted by the Buyer to its retailers, contractors and clients, even as final consumers, does not engage ACL in anyway.</p>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0; padding-left: 15px;">4. The above mentioned warranty excludes any other remedies and it has to be considered the only and exclusive remedy foreseen for the Buyer and its retailers, contractors and clients, with reference to Products purchased, being, expressively understood that any kind of limitation and/or discharge of responsibility provided by the present warranty is referred to both (I) the responsibility as against any third parties, pursuant to the legislation regarding the manufacturer responsibility and (II) the warranty provided by the law in force.</p>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 10px; margin: 0; padding-left: 15px;">5. The foregoing warranties are in lieu of all other warranties and Seller makes no other warranties whether written, oral, express, implied or statutory, including, but not limited to, warranties of merchantability or fitness for particular purpose.</p>

                                <!-- Section 6 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 5px; display: block;">6. DISCLAIMER, LIMITED LIABILITY</strong>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0; padding-left: 15px;">1. Buyer, before proceeding to the purchase of the Products is recommended to duly examine the Seller documentation relevant to the technical characteristics of the Products, in order to evaluate if the Products are fitted for the expected use.</p>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 5px; margin: 0; padding-left: 15px;">Products may include also specific Operational software. While every effort is made to ensure the accuracy of the information provided, the user must be aware that the results provided by products offered may be not absolutely error free.</p>
                                <p style="font-size: 11px;line-height: 15px; padding: 0 0 10px; margin: 0; padding-left: 15px;">In no event shall Seller be liable for special, indirect, incidental, exemplary, punitive or consequential damages including, but not limited to, loss of profits or revenue, caused by the purchase and use of the Products. Buyer assumes all risks and liability resulting from use of the Products purchased, whether used separately or in combination with other products.</p>

                                <!-- Section 7 -->
                                <strong style="font-weight: 700;font-size: 12px; padding: 0 0 5px; display: block; color: #484848;">7. GOVERNING LAW, DISPUTE</strong>
                                <p style="font-size: 12px;line-height: 20px; padding: 0 0 10px; margin: 0; color: #484848;">Buyer hereby irrevocably agrees that disputes arising under this Agreement unless resolved mutually, shall be subject to jurisdiction of courts in New Delhi, India.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>