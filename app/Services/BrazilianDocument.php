<?php
declare(strict_types=1);
namespace App\Services;

final class BrazilianDocument
{
    public static function normalize(string $value): string { return preg_replace('/\D/', '', $value); }
    public static function valid(string $value): bool
    {
        $n=self::normalize($value);
        if(!in_array(strlen($n),[11,14],true)||preg_match('/^(\d)\1+$/D',$n))return false;
        $digits=array_map('intval',str_split($n));
        foreach(strlen($n)===11?[9,10]:[12,13] as $length){
            $sum=0;
            for($i=0;$i<$length;$i++)$sum+=$digits[$i]*(strlen($n)===11?$length+1-$i:(($length-$i-1)%8)+2);
            $check=$sum%11<2?0:11-$sum%11;
            if($check!==$digits[$length])return false;
        }
        return true;
    }
}
