<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Illuminate\Support\Facades\DB;

class CodeHelper extends Controller 
{
    const PREFIXES = [ 
        'order' => 'ORD', 
        'product' => 'PRD', 
        'user' => 'USR' ];

    public static function generateCode($type)
    {
        if (!array_key_exists($type, self::PREFIXES)) { 
            throw new \InvalidArgumentException("Invalid type provided."); 
        }

        $prefix = self::PREFIXES[$type];
        $currentDate = Carbon::now()->format('Ymd');
        
        $sequenceRecord = DB::table('order_sequences')->where('type', $type)->lockForUpdate()->first();

        if (!$sequenceRecord) {
            DB::table('order_sequences')->insert([ 
                'type' => $type, 
                'current_date' => Carbon::now()->format('Y-m-d'), 
                'current_number' => 0, 
                'created_at' => Carbon::now(), 
                'updated_at' => Carbon::now() 
            ]);

            $sequenceRecord = DB::table('order_sequences')->where('type', $type)->lockForUpdate()->first();
        }

    }
}