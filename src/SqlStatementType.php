<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

enum SqlStatementType: string
{
    case Select = 'SELECT';
    case Insert = 'INSERT';
    case Update = 'UPDATE';
    case Delete = 'DELETE';
    case Ddl = 'DDL';
    case Other = 'OTHER';
}
