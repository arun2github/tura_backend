// app/Http/Controllers/AESController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AESService;

class AESController extends Controller
{
    protected $aesService;

    public function __construct(AESService $aesService)
    {
        $this->aesService = $aesService;
    }

    public function encryptData()
    {
        // Sample key and request parameter (You can dynamically set these as needed)
        $key = "pWhMnIEMc4q6hKdi2Fx50Ii8CKAoSIqv9ScSpwuMHM4=";
        $requestParameter  = "1000605|DOM|IN|INR|26|Other|http://localhost/success1.php|https://www.cpri.in/cprisbipg/fail.php|SBIEPAY|1233456117890|2|NB|ONLINE|ONLINE";

        // Encrypt the data
        $encryptedData = $this->aesService->encrypt($requestParameter, $key);
        return $encryptedData;
    }

    public function decryptData(Request $request)
    {
        // Assuming the encrypted data comes from the request
        $encryptedData = $request->input('encrypted_data');
        $key = "pWhMnIEMc4q6hKdi2Fx50Ii8CKAoSIqv9ScSpwuMHM4=";

        // Decrypt the data
        $decryptedData = $this->aesService->decrypt($encryptedData, $key);
        return $decryptedData;
    }
}
