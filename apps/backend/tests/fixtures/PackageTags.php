<?php
namespace Phalcon\Test\Fixtures;
// @codingStandardsIgnoreStart
class PackageTags
{
    public static function get($records = null)
    {
        // packageTagId, tag, type, order, packageId, createdAt
        $template = "(%d, '%s', %d, %d, %d, '%s')";
        
        for ($i = 1; $i <= 3; $i++)
        {
            $j = 0;
            foreach(\Phalcon\DI::getDefault()->getShared('config')->application->package->tags as $type => $tag)
            {
                $i = $i+$j;
                $data[] = sprintf($template, $i, "{$tag}", $type, $i, $i, "2014-01-17 1{$i}:00:00");
                $j++;
            }
        }
        
        return $data;
    }
}