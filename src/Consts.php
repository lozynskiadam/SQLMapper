<?php

namespace SQLMapper;

class Consts
{
    const SQL_MAPPER_PROPERTIES = 'SQLMapperProperties';

    const EXCEPTION_TABLE_NOT_EXISTS = 'Table `%s` does not exists.';
    const EXCEPTION_TABLE_NOT_CONTAIN_PRIMARY_KEY_COLUMN = 'Table `%s` not contain a primary key column.';
    const EXCEPTION_PRIMARY_KEY_VALUE_NOT_SET_WHILE_ERASING = 'Can not erase - Primary key value not given.';
    const EXCEPTION_ADDING_PROBLEM = 'Problem occurred while inserting new row to table.';
    const EXCEPTION_SAVING_PROBLEM = 'Problem occurred while updating row.';
    const EXCEPTION_ERASING_PROBLEM = 'Problem occurred while erasing row.';
    const EXCEPTION_WRONG_PARAMS_AMOUNT = 'Wrong parameters amount.';
    const EXCEPTION_QUERY_NOT_DETERMINED = 'Query is not determined.';
    const EXCEPTION_COLUMN_IN_TABLE_NOT_EXISTS = 'Column `%s` not exists in table %s.';

    const SCHEMA_COLUMN_FIELD = 'Field';
    const SCHEMA_COLUMN_TYPE = 'Type';
    const SCHEMA_COLUMN_NULL = 'Null';
    const SCHEMA_COLUMN_KEY = 'Key';
    const SCHEMA_COLUMN_DEFAULT = 'Default';
    const SCHEMA_COLUMN_EXTRA = 'Extra';

    const SCHEMA_COLUMN_KEY_VALUE_PRIMARY = 'PRI';
    const SCHEMA_COLUMN_NULL_VALUE_TRUE = 'YES';
    const SCHEMA_COLUMN_NULL_VALUE_FALSE = 'NO';

}