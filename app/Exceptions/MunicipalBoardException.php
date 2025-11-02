<?php

namespace App\Exceptions;

use Exception;

class MunicipalBoardException extends Exception
{
    public function message()
    {
        return response()->json([
            'status' => 'failed',
            'message' => $this->getMessage()
        ]);
    }
}
