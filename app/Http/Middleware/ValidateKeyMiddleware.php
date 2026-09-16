<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Closure;

class ValidateKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $app_key = 'jongbright1805.dZbJyflcP9CCjQTxch8M0BehZQUczGmPL2ZRf92TpBCioniH7w7jxIJnS0DRLDfRNbYYpiQzmP3eXx7o2DZRNnoJxMU7AjfJaKstvupOmDlcDxBzG2x3cu4rtM08k7Qm@cilantro1';
        $mobile_key = 'jongbright1805.ePdTDfKjC737UpDxxC3OQx0BzZ4RPPJecQzDxihpZpubmMGrcy08bRJI2loDmXM2i27nJBjiGxmzR2uoHtk7Uw9QoA0MYmtNas8J9nLxjZ7SfQLcCfchYTBnDRfvlNcZ@cilantro.mobile';
        $tricoder_key = 'jongbright1805.tVbflcPzGmPL9CCj3YVfQ8M1JuBehZQUc92TpBCioniH7wfRMbYYpiQ7gtIJnS0DTxchRLDz8o2DmP3eXxZRNnoJxMU7AjfJaKbtvxzG2M083cu4wtupOmDlcDxBk7Ws@cilantro.tricorder';
        $cde_key = 'jongbright1805.Je8M9mCxcDxPfop0uwtAHJxZlN02mIKPnJbuMrnffkeRhQcjP77SRsDYc7QG2TzBl32GD73L7oxbiCctUynvCMRjJ4ZO92ipZDTXomRxfi8adcUpzxhmBD0BLQZjYNzQ@cilantro.cde';
        $c0_key = 'jongbright1805.z2MhJBt79c3ZDPwLpTDbQxJmP6YxYvjo4SMRJmcthQ8XGqCZV1yBfD8bPcTM7eIoSUcXtY0vQnQ3gJd2Pb@cilantro.c0';
        $pic2p_key = 'jongbright1805.MPjYVmMxZhQMnf8oUhQZJB0f77vIwtbqjQcZTSJaGCLtcPJg28ZtcRzL3pQDbXxMvDTnS4J7Mio6hY9wPbD3mPCCe1zBToYucv8x70@pic2p';
        $einstein_key = 'jongbright1805.DRfVmM8o7vIwtbJaSUhQZJjYxZhQMnB0f7cDbXxMvDTPJg28ZtcRzL3pQnS4J7BToYucMio6hY9wPbD3mPCCe1zGCLtqjQcZTv8x92@einstein';

        $request_key = $request->header('Y-RhPayload-C');

        if (!$request_key) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unavailable!'
            ], 401);
        }

        try {
            if (
                Crypt::decryptString($request_key) != $app_key 
                && Crypt::decryptString($request_key) != $mobile_key 
                && Crypt::decryptString($request_key) != $tricoder_key 
                && Crypt::decryptString($request_key) != $cde_key 
                && Crypt::decryptString($request_key) != $c0_key 
                && Crypt::decryptString($request_key) != $pic2p_key
                && Crypt::decryptString($request_key) != $einstein_key
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized!'
                ], 401);
            }
        } catch (DecryptException | InvalidFormatException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized!'
            ], 401);
        }


        return $next($request);
    }
}
