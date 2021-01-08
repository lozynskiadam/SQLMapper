<?php

namespace SQLMapper;

class Consts
{
    const EXCEPTION_TABLE_NOT_EXISTS = 'Table `%s` does not exists or does not contain a primary key column.';
    const EXCEPTION_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING = 'Can not erase - Primary key value not given.';
    const EXCEPTION_ADDING_PROBLEM = 'Problem occurred while inserting new row to table.';
    const EXCEPTION_SAVING_PROBLEM = 'Problem occurred while updating row.';
    const EXCEPTION_ERASING_PROBLEM = 'Problem occurred while erasing row.';
    const EXCEPTION_WRONG_PARAMS_AMOUNT = 'Wrong parameters amount.';
    const EXCEPTION_QUERY_NOT_DETERMINED = 'Query is not determined.';

    const SQL_MAPPER_PROPERTIES = 'SQLMapperProperties';
    const COLUMNS_KEY_COLUMN = 'Field';
    const COLUMNS_KEY_PRIMARY = 'PRI';
}