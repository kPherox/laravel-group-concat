<?php

namespace kPherox\GroupConcat\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder;

class GroupConcatServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('groupConcat', function(String $name, String $separator = null, Bool $distinct = false, String $columnName = null) {
            $separator = $separator ? "'$separator'" : '\',\'';
            $columnName = ' AS '.($columnName ?: $name);
            $name = $distinct ? 'DISTINCT '.$name : $name;
            $connection = config('database.default');
            switch (config("database.connections.{$connection}.driver"))
            {
                case 'mysql':
                case 'sqlite':
                    $sql = 'GROUP_CONCAT('.$name.' SEPARATOR '.$separator.')';
                    break;
                case 'pgsql':
                    $sql = 'ARRAY_TO_STRING(ARRAY(SELECT unnest(array_agg('.$name.'))), '.$separator.')';
                    break;
                case 'sqlsrv':
                    $sql = '(SELECT '.$name.' + \',\' FOR XML PATH(\'\'))';
                default:
                    throw new \Exception('Driver not supported.');
                    break;
            }

            return $this->selectRaw($sql . $columnName);
        });
    }
}
