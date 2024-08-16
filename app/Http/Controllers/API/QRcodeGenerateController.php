<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;


class QRcodeGenerateController extends Controller
{
    public function qrcode($ccm)
    {
        $user = User::where('ccm', $ccm)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $qrCode = QrCode::size(500)->generate($ccm);

        // Convert PNG data to base64 to embed it in the HTML
        $qrCodeBase64 = base64_encode($qrCode);
        $qrCodeHtml = '<img src="data:image/png;base64,' . $qrCodeBase64 . '">';

        $pdf = Pdf::loadView('qrcode-pdf', [
            'qrCode' => $qrCodeHtml,
            'user' => $user
        ]);

        return $pdf->download('qrcode.pdf');
    }
}
