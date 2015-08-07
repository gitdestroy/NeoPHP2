<?php

namespace NeoPHP\sql;

abstract class PostgreSQLConnection extends Connection
{
    public function getDatabaseType ()
    {
        return "pgsql";
    }
}

?>