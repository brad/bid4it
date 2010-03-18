<?php
$tests = array(
array(
'sql' => 'select * from `dog` where cat <> 4',
'expected_compiled' => 'select * from `dog` where `cat` <> 4',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
        	),
        'columns' => array(
        	0 => array('type'=>'glob','table'=>'', 'value'=>'*', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'dog'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'dog', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'cat',
                'type' => 'ident'
                ),
            'op' => '<>',
            'arg_2' => array(
                'value' => 4,
                'type' => 'int_val'
                )
            )
        )
),
array(
'sql' => 'select legs, hairy from dog',
'expected_compiled' => 'select `legs`, `hairy` from `dog`',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'legs',
            1 => 'hairy'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'legs', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'', 'value'=>'hairy', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'dog'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'dog', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select max(`length`) from dog',
'expected_compiled' => 'select max(`length`) from `dog`',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'max',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'length'
                		)
                	)
                )
            ),
        'columns' => array(
        	0 => array(
        		'type' => 'func', 
				'table' => '', 
				'value' => array(
					'name' => 'max',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'length'
							)
						)
					),
				'alias' => ''
				)
			),				
		'table_names' => array(
            0 => 'dog'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'dog', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select count(distinct country) from publishers',
'expected_compiled' => 'select count(distinct `country`) from `publishers`',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'args' => array(
                    0 => array(
                    	'quantifier' => 'distinct',
                    	'type' => 'ident',
                    	'value' => 'country'
         
                    	)
                    )
                )
            ),
        'columns' => array(
        	0 => array(
        		'type' => 'func',
				'table' => '',
				'value' => array(
					'name' => 'count',
					'args' => array(
						0 => array(
							'quantifier' => 'distinct',
							'type' => 'ident',
							'value' => 'country'
			 
							)
						)
					),
				'alias' => ''
				)
			),
        'table_names' => array(
            0 => 'publishers'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'publishers', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2',
'expected_compiled' => 'select `one`, `two` from `hairy` where `two` <> 4 and `one` = 2',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'one',
            1 => 'two'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'one', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'', 'value'=>'two', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'hairy'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'hairy', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 order by two',
'expected_compiled' => 'select `one`, `two` from `hairy` where `two` <> 4 and `one` = 2 order by `two` asc',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'one',
            1 => 'two'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'one', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'', 'value'=>'two', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'hairy'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'hairy', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                )
            ),
        'sort_order' => array(
            0 => array(
            	'value' => 'two',
            	'type' => 'ident',
            	'order' => 'asc'
            	)
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 limit 4 order by two ascending, dog descending',
'expected_compiled' => 'select `one`, `two` from `hairy` where `two` <> 4 and `one` = 2 order by `two` asc, `dog` desc limit 4',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'one',
            1 => 'two'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
 		'columns' => array(
 			0 => array( 'type'=>'ident', 'table'=>'', 'value'=>'one', 'alias'=>''),
 			1 => array( 'type'=>'ident', 'table'=>'', 'value'=>'two', 'alias'=>'')
 			),
        'table_names' => array(
            0 => 'hairy'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'hairy', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                )
            ),
        'limit_clause' => array(
            'start' => 0,
            'length' => 4
            ),
        'sort_order' => array(
        	0 => array(
        		'value' => 'two',
        		'type' => 'ident',
        		'order' => 'asc'
        		),
        	1 => array(
        		'value' => 'dog',
        		'type' => 'ident',
        		'order' => 'desc'
        		)
            )
        )
),
array(
'sql' => 'select foo.a from foo',
'expected_compiled' => 'select `foo`.`a` from `foo`',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'foo.a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'foo', 'value'=>'a', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'foo'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'foo', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select a as b, min(a) as baz from foo',
'expected_compiled' => 'select `a` as `b`, min(`a`) as `baz` from `foo`',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => 'b'
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>'b'),
        	1 => array(
        		'type' => 'func',
        		'table' => '',
        		'value' => array(
        			'name' => 'min',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'a'
							)
						),
					'alias' => 'baz'
						
					),
				'alias' => 'baz'
				)
			),	
        'set_function' => array(
            0 => array(
                'name' => 'min',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'a'
                		)
                	),
                'alias' => 'baz'
                )
            ),
        'table_names' => array(
            0 => 'foo'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'foo', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select a from foo as bar',
'expected_compiled' => 'select `a` from `foo` as `bar`',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'foo'
            ),
        'table_aliases' => array(
            0 => 'bar'
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'foo', 'alias'=>'bar')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select * from person where surname is not null and firstname = \'jason\'',
'expected_compiled' => 'select * from `person` where `surname` is not null and `firstname` = \'jason\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
       	'columns' => array(
       		0 => array('type'=>'glob', 'table'=>'', 'value'=>'*', 'alias'=>'')
       		),
        'column_tables' => array(
        	0 => ''
        	),
        'table_names' => array(
            0 => 'person'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'person', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'surname',
                    'type' => 'ident'
                    ),
                'op' => 'is',
                'neg' => true,
                'arg_2' => array(
                    'value' => '',
                    'type' => 'null'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'firstname',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'jason',
                    'type' => 'text_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select * from person where surname is null',
'expected_compiled' => 'select * from `person` where `surname` is null',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
        	),
        'columns' => array(
       		0 => array('type'=>'glob', 'table'=>'', 'value'=>'*', 'alias'=>'')
       		),
        'table_names' => array(
            0 => 'person'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'person', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'surname',
                'type' => 'ident'
                ),
            'op' => 'is',
            'arg_2' => array(
                'value' => '',
                'type' => 'null'
                )
            )
        )
),
array(
'sql' => 'select * from person where surname = \'\' and firstname = \'jason\'',
'expected_compiled' => 'select * from `person` where `surname` = \'\' and `firstname` = \'jason\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
        	),
        'columns' => array(
       		0 => array('type'=>'glob', 'table'=>'', 'value'=>'*', 'alias'=>'')
       		),
        'table_names' => array(
            0 => 'person'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'person', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'surname',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => '',
                    'type' => 'text_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'firstname',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'jason',
                    'type' => 'text_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select table_1.id, table_2.name from table_1, table_2 where table_2.table_1_id = table_1.id',
'expected_compiled' => 'select `table_1`.`id`, `table_2`.`name` from `table_1`, `table_2` where `table_2`.`table_1_id` = `table_1`.`id`',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'table_1.id',
            1 => 'table_2.name'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'table_1', 'value'=>'id', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'table_2', 'value'=>'name', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'table_1',
            1 => 'table_2'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'table_1', 'alias'=>''),
        	1 => array('type'=>'ident', 'value'=>'table_2', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => '',
            1 => ''
            ),
        'table_join' => array(
            0 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'table_2.table_1_id',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 'table_1.id',
                'type' => 'ident'
                )
            )
        )
),
array(
'sql' => 'select a from table_1 where a not in (select b from table_2) limit 1',
'expected_compiled' => 'select `a` from `table_1` where `a` not in (select `b` from `table_2`) limit 1',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'table_1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'table_1', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'a',
                'type' => 'ident'
                ),
            'op' => 'in',
            'neg' => true,
            'arg_2' => array(
                'value' => array(
                    'command' => 'select',
                    'columns' => array(
                    	0 => array('type'=>'ident', 'table'=>'', 'value'=>'b', 'alias'=>'')
                    	),
                    'column_tables' => array(
                        0 => ''
                        ),
                    'column_names' => array(
                        0 => 'b'
                        ),
                    'column_aliases' => array(
                        0 => ''
                        ),
                    'table_names' => array(
                        0 => 'table_2'
                        ),
                    'table_aliases' => array(
                        0 => ''
                        ),
                    'tables' => array(
                    	0 => array('type'=>'ident', 'value'=>'table_2', 'alias'=>'')
                    	),
                    'table_join_clause' => array(
                        0 => ''
                        )
                    ),
                'type' => 'command'
                )
            ),
          'limit_clause'=> array(
          	 'start'=>0,
          	 'length'=>1
          	 )
        )
),
array(
'sql' => 'select a from table_1 where a in (select b from table_2 where c not in (select d from table_3))',
'expected_compiled' => 'select `a` from `table_1` where `a` in (select `b` from `table_2` where `c` not in (select `d` from `table_3`))',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'columns' => array(
        	0 => array( 'type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'table_1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'table_1', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'a',
                'type' => 'ident'
                ),
            'op' => 'in',
            'arg_2' => array(
                'value' => array(
                    'command' => 'select',
                    'columns' => array(
                    	0 => array('type'=>'ident', 'table'=>'', 'value'=>'b', 'alias'=>'')
                    	),
                    'column_tables' => array(
                        0 => ''
                        ),
                    'column_names' => array(
                        0 => 'b'
                        ),
                    'column_aliases' => array(
                        0 => ''
                        ),
                    'table_names' => array(
                        0 => 'table_2'
                        ),
                    'table_aliases' => array(
                        0 => ''
                        ),
                    'tables' => array(
                    	0 => array('type'=>'ident', 'value'=>'table_2', 'alias'=>'')
                    	),
                    'table_join_clause' => array(
                        0 => ''
                        ),
                    'where_clause' => array(
                        'arg_1' => array(
                            'value' => 'c',
                            'type' => 'ident'
                            ),
                        'op' => 'in',
                        'neg' => true,
                        'arg_2' => array(
                            'value' => array(
                                'command' => 'select',
                                'columns' => array(
                                	0 => array('type'=>'ident', 'table'=>'', 'value'=>'d', 'alias'=>'')
                                	),
                                'column_tables' => array(
                                    0 => ''
                                    ),
                                'column_names' => array(
                                    0 => 'd'
                                    ),
                                'column_aliases' => array(
                                    0 => ''
                                    ),
                                'table_names' => array(
                                    0 => 'table_3'
                                    ),
                                'table_aliases' => array(
                                    0 => ''
                                    ),
                                'tables' => array(
                                	0 => array('type'=>'ident', 'value'=>'table_3', 'alias'=>'')
                                	),
                                'table_join_clause' => array(
                                    0 => ''
                                    )
                                ),
                            'type' => 'command'
                            )
                        )
                    ),
                'type' => 'command'
                )
            )
        )
),
array(
'sql' => 'select a from table_1 where a in (1, 2, 3)',
'expected_compiled' => 'select `a` from `table_1` where `a` in (1, 2, 3)',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'table_1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'table_1', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'a',
                'type' => 'ident'
                ),
            'op' => 'in',
            'arg_2' => array(
                'value' => array(
                    0 => 1,
                    1 => 2,
                    2 => 3
                    ),
                'type' => array(
                    0 => 'int_val',
                    1 => 'int_val',
                    2 => 'int_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select count(child_table.name) from parent_table ,child_table where parent_table.id = child_table.id',
'expected_compiled' => 'select count(`child_table`.`name`) from `parent_table`, `child_table` where `parent_table`.`id` = `child_table`.`id`',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'args' => array(
                    0 => array(
                    	'type' => 'ident',
                    	'value' => 'child_table.name'
                    	)
                    )
                )
            ),
        'columns' => array(
        	0 => array(
        		'type' => 'func',
        		'table' => '',
        		'value' => array(
					'name' => 'count',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'child_table.name'
							)
						)
					),
				'alias' => ''
				)
			),
        'table_names' => array(
            0 => 'parent_table',
            1 => 'child_table'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'parent_table', 'alias'=>''),
        	1 => array('type'=>'ident', 'value'=>'child_table', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => '',
            1 => ''
            ),
        'table_join' => array(
            0 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'parent_table.id',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 'child_table.id',
                'type' => 'ident'
                )
            )
        )
),
array(
'sql' => 'select parent_table.name, count(child_table.name) from parent_table ,child_table where parent_table.id = child_table.id group by parent_table.name',
'expected_compiled' => 'select `parent_table`.`name`, count(`child_table`.`name`) from `parent_table`, `child_table` where `parent_table`.`id` = `child_table`.`id` group by `parent_table`.`name`',
'expect' => array(
        'command' => 'select',
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'parent_table', 'value'=>'name', 'alias'=>''),
        	1 => array(
        		'type' => 'func',
        		'table' => '',
        		'value' => array(
					'name' => 'count',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'child_table.name'
							)
						)
					),
				'alias' => ''
				)
			),
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'parent_table.name'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'args' => array(
                    0 => array(
                    	'type' => 'ident',
                    	'value' => 'child_table.name'
                    	)
                    )
                )
            ),
        'table_names' => array(
            0 => 'parent_table',
            1 => 'child_table'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'parent_table', 'alias'=>''),
        	1 => array('type'=>'ident', 'value'=>'child_table', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => '',
            1 => ''
            ),
        'table_join' => array(
            0 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'parent_table.id',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 'child_table.id',
                'type' => 'ident'
                )
            ),
        'group_by' => array(
            0 => 'parent_table.name'
            )
        )
),
array(
'sql' => 'select * from cats where furry = 1 group by name, type',
'expected_compiled' => 'select * from `cats` where `furry` = 1 group by `name`, `type`',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
        	),
        'columns' => array(
        	0 => array('type'=>'glob', 'table' => '', 'value' => '*', 'alias' => '')
        	),
        'table_names' => array(
            0 => 'cats'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'cats', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'furry',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'group_by' => array(
            0 => 'name',
            1 => 'type'
            )
        )
),
array(
'sql' => 'select a, max(b) as x, sum(c) as y, min(d) as z from e',
'expected_compiled' => 'select `a`, max(`b`) as `x`, sum(`c`) as `y`, min(`d`) as `z` from `e`',
'expect' => array(
        'command' => 'select',
        'columns' => array(
        	0 => array('type'=> 'ident', 'table'=>'', 'value'=>'a', 'alias'=>''),
        	1 => array(
        		'type' => 'func', 
        		'table' => '',
        		'value' => array(
					'name' => 'max',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'b'
							)
						),
					'alias' => 'x'
					),
				'alias' => 'x'
				),
			2 => array(
				'type' => 'func',
				'table' => '',
				'value' => array(
					'name' => 'sum',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'c'
							)
						),
					'alias' => 'y'
					),
				'alias' => 'y'
				),
			3 => array(
				'type' => 'func',
				'table' => '',
				'value' => array(
					'name' => 'min',
					'args' => array(
						0 => array(
							'type' => 'ident',
							'value' => 'd'
							)
						),
					'alias' => 'z'
					),
				'alias' => 'z'
				)
			),
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'set_function' => array(
            0 => array(
                'name' => 'max',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'b'
                		)
                	),
                'alias' => 'x'
                ),
            1 => array(
                'name' => 'sum',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'c'
                		)
                	),
                'alias' => 'y'
                ),
            2 => array(
                'name' => 'min',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'd'
                		)
                	),
                'alias' => 'z'
                )
            ),
        'table_names' => array(
            0 => 'e'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'e', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select clients_translation.id_clients_prefix, clients_translation.rule_number,
       clients_translation.pattern, clients_translation.rule
       from clients, clients_prefix, clients_translation
       where (clients.id_softswitch = 5)
         and (clients.id_clients = clients_prefix.id_clients)
         and clients.enable=\'y\'
         and clients.unused=\'n\'
         and (clients_translation.id_clients_prefix = clients_prefix.id_clients_prefix)
         order by clients_translation.id_clients_prefix,clients_translation.rule_number',
'expected_compiled' => 'select `clients_translation`.`id_clients_prefix`, `clients_translation`.`rule_number`, `clients_translation`.`pattern`, `clients_translation`.`rule` from `clients`, `clients_prefix`, `clients_translation` where (`clients`.`id_softswitch` = 5) and (`clients`.`id_clients` = `clients_prefix`.`id_clients`) and `clients`.`enable` = \'y\' and `clients`.`unused` = \'n\' and (`clients_translation`.`id_clients_prefix` = `clients_prefix`.`id_clients_prefix`) order by `clients_translation`.`id_clients_prefix` asc, `clients_translation`.`rule_number` asc',
'expect' => array(
        'command' => 'select',
        'columns' => array(
        	0 => array('type'=>'ident','table'=>'clients_translation','value'=>'id_clients_prefix', 'alias'=>''),
        	1 => array('type'=>'ident','table'=>'clients_translation','value'=>'rule_number', 'alias'=>''),
        	2 => array('type'=>'ident','table'=>'clients_translation','value'=>'pattern', 'alias'=>''),
        	3 => array('type'=>'ident','table'=>'clients_translation','value'=>'rule', 'alias'=>'')
        	),
        'column_tables' => array(
            0 => '',
            1 => '',
            2 => '',
            3 => ''
            ),
        'column_names' => array(
            0 => 'clients_translation.id_clients_prefix',
            1 => 'clients_translation.rule_number',
            2 => 'clients_translation.pattern',
            3 => 'clients_translation.rule'
            ),
        'column_aliases' => array(
            0 => '',
            1 => '',
            2 => '',
            3 => ''
            ),
        'table_names' => array(
            0 => 'clients',
            1 => 'clients_prefix',
            2 => 'clients_translation'
            ),
        'table_aliases' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'clients', 'alias'=>''),
        	1 => array('type'=>'ident', 'value'=>'clients_prefix', 'alias'=>''),
        	2 => array('type'=>'ident', 'value'=>'clients_translation', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'table_join' => array(
            0 => ',',
            1 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'value' => 'clients.id_softswitch',
                            'type' => 'ident'
                            ),
                        'op' => '=',
                        'arg_2' => array(
                            'value' => 5,
                            'type' => 'int_val'
                            )
                        ),
                    'type' => 'subclause'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'arg_1' => array(
                        'value' => array(
                            'arg_1' => array(
                                'value' => 'clients.id_clients',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'clients_prefix.id_clients',
                                'type' => 'ident'
                                )
                            ),
                        'type' => 'subclause'
                        )
                    ),
                'op' => 'and',
                'arg_2' => array(
                    'arg_1' => array(
                        'arg_1' => array(
                            'value' => 'clients.enable',
                            'type' => 'ident'
                            ),
                        'op' => '=',
                        'arg_2' => array(
                            'value' => 'y',
                            'type' => 'text_val'
                            )
                        ),
                    'op' => 'and',
                    'arg_2' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'clients.unused',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'n',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => array(
                                    'arg_1' => array(
                                        'value' => 'clients_translation.id_clients_prefix',
                                        'type' => 'ident'
                                        ),
                                    'op' => '=',
                                    'arg_2' => array(
                                        'value' => 'clients_prefix.id_clients_prefix',
                                        'type' => 'ident'
                                        )
                                    ),
                                'type' => 'subclause'
                                )
                            )
                        )
                    )
                )
            ),
        'sort_order' => array(
        	0 => array(
        		'value' => 'clients_translation.id_clients_prefix',
        		'type' => 'ident',
        		'order' => 'asc'
        		),
        	1 => array(
        		'value' => 'clients_translation.rule_number',
        		'type' => 'ident',
        		'order' => 'asc'
        		)
            
            )
        )
),
array(
'sql' => 'SELECT column1,column2
FROM table1
WHERE (column1=\'1\' AND column2=\'1\') OR (column3=\'1\' AND column4=\'1\')',
'expected_compiled' => 'select `column1`, `column2` from `table1` where (`column1` = \'1\' and `column2` = \'1\') or (`column3` = \'1\' and `column4` = \'1\')',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'column1',
            1 => 'column2'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'column1', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'', 'value'=>'column2', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'table1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'table1', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'column1',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'column2',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                ),
            'op' => 'or',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'column3',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'column4',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                )
            )
        )
),
array(
'sql' => '-- Test Comment',
'expect' => 'Parse error: Nothing to do on line 1
-- Test Comment
                ^ found: "*end of input*"'

),
array(
'sql' => '# Test Comment',
'expect' => 'Parse error: Nothing to do on line 1
# Test Comment
               ^ found: "*end of input*"'

),
array(
'sql' => 'SELECT name FROM people WHERE id > 1 AND (name = \'arjan\' OR name = \'john\')',
'expected_compiled' => 'select `name` from `people` where `id` > 1 and (`name` = \'arjan\' or `name` = \'john\')',

'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'name'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'name', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'people'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'people', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'id',
                    'type' => 'ident'
                    ),
                'op' => '>',
                'arg_2' => array(
                    'value' => 1,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'name',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'arjan',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'or',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'name',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'john',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                )
            )
        )
),
array(
'sql' => 'select * from test where (field1 = \'x\' and field2 <>\'y\') or field3 = \'z\'',
'expected_compiled' => 'select * from `test` where (`field1` = \'x\' and `field2` <> \'y\') or `field3` = \'z\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
        	),
       	'columns' => array(
       		0 => array('type'=>'glob', 'table'=>'', 'value'=>'*', 'alias'=>'')
       		),
        'table_names' => array(
            0 => 'test'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'test', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'field1',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'x',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'field2',
                                'type' => 'ident'
                                ),
                            'op' => '<>',
                            'arg_2' => array(
                                'value' => 'y',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                ),
            'op' => 'or',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'field3',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'z',
                    'type' => 'text_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select a, d from b inner join c on b.a = c.a',
'expected_compiled' => 'select `a`, `d` from `b` inner join `c` on `b`.`a` = `c`.`a`',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'a',
            1 => 'd'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'', 'value'=>'d', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'b',
            1 => 'c'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'b', 'alias'=>''),
        	1 => array('type'=>'ident', 'value'=>'c', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => '',
            1 => array(
                'arg_1' => array(
                    'value' => 'b.a',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'c.a',
                    'type' => 'ident'
                    )
                )
            ),
        'table_join' => array(
            0 => 'inner join'
            )
        )
),
array(
'sql' => 'select a, d from b inner join c on b.a = c.a left outer join q on r < m',
'expected_compiled' => 'select `a`, `d` from `b` inner join `c` on `b`.`a` = `c`.`a` left outer join `q` on `r` < `m`',

'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'a',
            1 => 'd'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'columns' => array(
        	0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>''),
        	1 => array('type'=>'ident', 'table'=>'', 'value'=>'d', 'alias'=>'')
        	),
        'table_names' => array(
            0 => 'b',
            1 => 'c',
            2 => 'q'
            ),
        'table_aliases' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'tables' => array(
        	0 => array('type'=>'ident', 'value'=>'b', 'alias'=>''),
        	1 => array('type'=>'ident', 'value'=>'c', 'alias'=>''),
        	2 => array('type'=> 'ident', 'value'=>'q', 'alias'=>'')
        	),
        'table_join_clause' => array(
            0 => '',
            1 => array(
                'arg_1' => array(
                    'value' => 'b.a',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'c.a',
                    'type' => 'ident'
                    )
                ),
            2 => array(
                'arg_1' => array(
                    'value' => 'r',
                    'type' => 'ident'
                    ),
                'op' => '<',
                'arg_2' => array(
                    'value' => 'm',
                    'type' => 'ident'
                    )
                )
            ),
        'table_join' => array(
            0 => 'inner join',
            1 => 'left outer join'
            )
        )
),
array(
'sql' => 'select a, length(a) as __a_length from Foo',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo`',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'a'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>''),
		1 => array(
			'type'=>'func', 
			'table' => '',
			'value' =>array(
				'name' => 'length',
				'args' => array(
					0 => array(
						'type' => 'ident',
						'value' => 'a'
						)
					),
				
				'alias' => '__a_length'
				),
			'alias' => '__a_length'
			)
		),
	'set_function' => array(
		0 => array(
			'name' => 'length',
			'args' => array(
				0 => array(
					'type' => 'ident',
					'value' => 'a'
					)
				),
			
			'alias' => '__a_length'
			),
		),
	'table_names' => array(
		0 => 'Foo'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value'=>'Foo', 'alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		)
	)
	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(b)>c',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(`b`) > `c`',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'a'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident', 'table' => '', 'value'=>'a', 'alias'=>''),
		1 => array(
			'type' => 'func',
			'table' => '',
			'value' => array(
				'name' => 'length',
				'args' => array(
					0 => array(
						'type' => 'ident',
						'value' => 'a'
						)
					),
				
				'alias' => '__a_length'
				),
			'alias' => '__a_length'
			)
		),
	
	'set_function' => array(
		0 => array(
			'name' => 'length',
			'args' => array(
				0 => array(
					'type' => 'ident',
					'value' => 'a'
					)
				),
			
			'alias' => '__a_length'
			),
		),
	'table_names' => array(
		0 => 'Foo'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value' => 'Foo', 'alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => array(
				'name' => 'abs',
				'args' => array(
					0 => array(
						'type'=>'ident',
						'value' => 'b'
						)
					)
				),
			'type' => 'function'
			),
		'op' => '>',
		'arg_2' => array(
			'value' => 'c',
			'type' => 'ident'
			
			)
		)
	)
	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(length(b))>c',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(length(`b`)) > `c`',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'a'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident', 'table'=>'', 'value'=>'a', 'alias'=>''),
		1 => array(
			'type' => 'func',
			'table' => '',
			'value' => array(
				'name' => 'length',
				'args' => array(
					0 => array(
						'type' => 'ident',
						'value' => 'a'
						)
					),
				
				'alias' => '__a_length'
				),
			'alias' => '__a_length'
			)
		),
	'set_function' => array(
		0 => array(
			'name' => 'length',
			'args' => array(
				0 => array(
					'type' => 'ident',
					'value' => 'a'
					)
				),
			
			'alias' => '__a_length'
			),
		),
	'table_names' => array(
		0 => 'Foo'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value'=>'Foo', 'alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => array(
				'name' => 'abs',
				'args' => array(
					0 => array(
						'type'=>'function',
						'value' => array(
							'name' => 'length',
							'args' => array(
								0 => array(
									'type' => 'ident',
									'value' => 'b'
									)
								)
							)
						)
					)
				),
			'type' => 'function'
			),
		'op' => '>',
		'arg_2' => array(
			'value' => 'c',
			'type' => 'ident'
			
			)
		)
	)
	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(length(b))>abs(length(c))',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(length(`b`)) > abs(length(`c`))',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'a'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident','table'=>'','value'=>'a','alias'=>''),
		1 => array(
			'type'=>'func',
			'table'=>'',
			'value'=>array(
				'name' => 'length',
				'args' => array(
					0 => array(
						'type' => 'ident',
						'value' => 'a'
						)
					),
				
				'alias' => '__a_length'
				),
			'alias'=>'__a_length'
			)
		),
	'set_function' => array(
		0 => array(
			'name' => 'length',
			'args' => array(
				0 => array(
					'type' => 'ident',
					'value' => 'a'
					)
				),
			
			'alias' => '__a_length'
			),
		),
	'table_names' => array(
		0 => 'Foo'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables'=>array(
		0 => array('type'=>'ident','value'=>'Foo','alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => array(
				'name' => 'abs',
				'args' => array(
					0 => array(
						'type'=>'function',
						'value' => array(
							'name' => 'length',
							'args' => array(
								0 => array(
									'type' => 'ident',
									'value' => 'b'
									)
								)
							)
						)
					)
				),
			'type' => 'function'
			),
		'op' => '>',
		'arg_2' => array(
			
			'value' => array(
				'name' => 'abs',
				'args' => array(
					0 => array(
						'type'=>'function',
						'value' => array(
							'name' => 'length',
							'args' => array(
								0 => array(
									'type' => 'ident',
									'value' => 'c'
									)
								)
							)
						)
					)
				),
			'type' => 'function'
			)
		)
	)
	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(length(b))>abs(length(c)) order by year(`date`)',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(length(`b`)) > abs(length(`c`)) order by year(`date`) asc',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'a'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident','table'=>'','value'=>'a','alias'=>''),
		1 => array(
			'type'=>'func',
			'table'=>'',
			'value'=> array(
				'name' => 'length',
				'args' => array(
					0 => array(
						'type' => 'ident',
						'value' => 'a'
						)
					),
				
				'alias' => '__a_length'
				),
			'alias' => '__a_length'
			)
		),
	'set_function' => array(
		0 => array(
			'name' => 'length',
			'args' => array(
				0 => array(
					'type' => 'ident',
					'value' => 'a'
					)
				),
			
			'alias' => '__a_length'
			),
		),
	'table_names' => array(
		0 => 'Foo'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident','value'=>'Foo','alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => array(
				'name' => 'abs',
				'args' => array(
					0 => array(
						'type'=>'function',
						'value' => array(
							'name' => 'length',
							'args' => array(
								0 => array(
									'type' => 'ident',
									'value' => 'b'
									)
								)
							)
						)
					)
				),
			'type' => 'function'
			),
		'op' => '>',
		'arg_2' => array(
			
			'value' => array(
				'name' => 'abs',
				'args' => array(
					0 => array(
						'type'=>'function',
						'value' => array(
							'name' => 'length',
							'args' => array(
								0 => array(
									'type' => 'ident',
									'value' => 'c'
									)
								)
							)
						)
					)
				),
			'type' => 'function'
			)
		),
	'sort_order' => array(
		0 => array(
			
			'value' => array(
				'name' => 'year',
				'args' => array(
					0 => array(
						'type' => 'ident',
						'value' => 'date'
						)
					)
				),
			'type' => 'function',
			'order' => 'asc'
			)
		)
	)
),
array(
'sql' => 'select name, institution from Degrees where Degrees.profileid=\'$id\'',
'expected_compiled' => 'select `name`, `institution` from `Degrees` where `Degrees`.`profileid` = \'$id\'',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'name',
		1 => 'institution'
		),
	'column_aliases' => array(
		0 => '',
		1 => ''
		),
	'column_tables' => array(
		0 => '',
		1 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident','table'=>'','value'=>'name','alias'=>''),
		1 => array('type'=>'ident','table'=>'','value'=>'institution','alias'=>'')
		),
	'table_names' => array(
		0 => 'Degrees'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value'=>'Degrees', 'alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => 'Degrees.profileid',
			'type' => 'ident'
			),
		'op' => '=',
		'arg_2' => array(
			
			'value' => '$id',
			'type' => 'text_val'
			)
		)
	)
),
array(
'sql' => 'select name, institution from Degrees where match (`Institution`) AGAINST ("Home")',
'expected_compiled' => 'select `name`, `institution` from `Degrees` where match (`Institution`) against (\'Home\')',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'name',
		1 => 'institution'
		),
	'column_aliases' => array(
		0 => '',
		1 => ''
		),
	'column_tables' => array(
		0 => '',
		1 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident','table'=>'','value'=>'name','alias'=>''),
		1 => array('type'=>'ident','table'=>'','value'=>'institution','alias'=>'')
		),
	'table_names' => array(
		0 => 'Degrees'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value'=>'Degrees', 'alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			'type' => 'match',
			'value' => array(
				0 => array(
					'type'=>'ident',
					'value'=>'Institution'
					)
				),
			
			'against' => 'Home'
			)
		)
	)
),
array(
'sql' => 'select name, institution, pg.Name from Degrees left join (select * from Programs) as pg on pg.degreeid = Degrees.degreeid where match (`Institution`) AGAINST ("Home")',
'expected_compiled' => 'select `name`, `institution`, `pg`.`Name` from `Degrees` left join (select * from `Programs`) as `pg` on `pg`.`degreeid` = `Degrees`.`degreeid` where match (`Institution`) against (\'Home\')',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'name',
		1 => 'institution',
		2 => 'pg.Name'
		),
	'column_aliases' => array(
		0 => '',
		1 => '',
		2 => ''
		),
	'column_tables' => array(
		0 => '',
		1 => '',
		2 => ''
		),
	'columns' => array(
		0 => array('type'=>'ident','table'=>'','value'=>'name','alias'=>''),
		1 => array('type'=>'ident','table'=>'','value'=>'institution','alias'=>''),
		2 => array('type'=>'ident','table'=>'pg','value'=>'Name','alias'=>'')
		),
	'table_names' => array(
		0 => 'Degrees'
		),
	'table_aliases' => array(
		0 => '',
		1 => 'pg'
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value'=>'Degrees', 'alias'=>''),
		1 => array(
			'type'=>'subselect',
			'value'=> array(
				'command'=>'select',
				'columns' => array(
					0 => array('type'=>'glob', 'table'=>'', 'value'=>'*', 'alias'=>'')
					),
				'column_tables'=>array(
					0 => ''
					),
				'column_names'=>array(
					0 => '*'
					),
				'column_aliases'=>array(
					0 => ''
					),
				'table_names' => array(
					0 => 'Programs'
					),
				'table_aliases' => array(
					0 => ''
					),
				'tables' => array(
					0 => array('type'=>'ident','value'=>'Programs','alias'=>'')
					),
				'table_join_clause' => array(
					0 => ''
					)
				),
			'alias' => 'pg'
			)
		),
	'table_join' => array(
		0 => 'left join'
		),
	'table_join_clause' => array(
		0 => '',
		1 => array(
			'arg_1'=> array(
				'value'=>'pg.degreeid',
				'type'=>'ident'
				),
			'op'=>'=',
			'arg_2'=> array(
				'value'=>'Degrees.degreeid',
				'type'=>'ident'
				)
			)
		),
	'where_clause' => array(
		'arg_1' => array(
			'type' => 'match',
			'value' => array(
				0 => array(
					'type'=>'ident',
					'value'=>'Institution'
					)
				),
			
			'against' => 'Home'
			)
		)
	)
),
array(
'sql' => 'select * from pages where date_sub(now(), interval 1 day) < ExpiryDate',
'expected_compiled' => 'select * from `pages` where date_sub(now(), interval 1 day) < `ExpiryDate`',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => '*'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	'columns' => array(
		0 => array('type'=>'glob','table'=>'','value'=>'*','alias'=>'')
		),
	'table_names' => array(
		0 => 'pages'
		),
	'table_aliases' => array(
		0 => ''
		),
	'tables' => array(
		0 => array('type'=>'ident', 'value'=>'pages', 'alias'=>'')
		),
	'table_join_clause' => array(
		0 => ''
		),
	
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => array(
				'name' => 'date_sub',
				'args' => array(
					0 => array(
						'type'=>'function',
						'value' => array(
							'name' => 'now',
							'args' => array()
							)
						),
					1 => array(
						'type'=>'interval',
						'value'=>1,
						'expression_type'=>'int_val',
						'unit'=>'day'
						)
					)
				
				),
			'type' => 'function'
			),
		'op' => '<',
		'arg_2' => array(
			
			'value' => 'ExpiryDate',
			'type' => 'ident'
			)
		),
	)
)


);
?>
