<?php
namespace PLite\Core\Dao;


class SQLServer extends DaoDriver {

    /**
     * 根据配置创建DSN
     * @param array $config 数据库连接配置
     * @return string
     */
    public function buildDSN(array $config){
//        dumpout($config);
        $dsn  =   "sqlsrv:Database={$config['dbname']};Server={$config['host']}";
        if(!empty($config['port'])) {
            $dsn  .= ','.$config['port'];
        }
//        $dsn  =   "sqlsrv:Server={$config['host']}";
//        if(isset($config['dbname'])){
//            $dsn .= ";Database={$config['dbname']}";
//        }
//        if(!empty($config['port'])) {
//            $dsn  .= ','.$config['port'];
//        }
        return $dsn;
    }

    public function escape($field){
        return "[{$field}]";
    }


    /**
     * 编译组件成适应当前数据库的SQL字符串
     * @param array $comps  复杂SQL的组成部分
     * @param int $actiontype 操作类型
     * @return string
     */
    public function compile(array $comps,$actiontype){
        $components = array(
            'distinct'=>'',
            'top' => '',
            'fields'=>' * ', //查询的表域情况
            'join'=>'',     //join部分，需要带上join关键字
            'where'=>'', //where部分
            'group'=>'', //分组 需要带上group by
            'having'=>'',//having子句，依赖$group存在，需要带上having部分
            'order'=>'',//排序，不需要带上order by
        );
        $components = array_merge($components,$comps);
        if($components['distinct']){//为true或者1时转化为distinct关键字
            $components['distinct'] = 'distinct';
        }
        $sql = " select \r\n {$components['distinct']} \r\n {$components['top']} \r\n {$components['fields']} \r\n  from \r\n  {$components['table']} \r\n ";

        //group by，having 加上关键字(对于如group by的组合关键字，只要判断第一个是否存在)如果不是以该关键字开头  则自动添加
        if($components['where']){
            $components['where'] = ((0 !== stripos(trim($components['where']),'where'))?' where ':'').$components['where'];
        }
        if($components['group'] ){
            $components['group'] = ((0 !== stripos(trim($components['group']),'group'))?' group by ':'').$components['group'];
        }
        if( $components['having']){
            $components['having'] = ((0 !== stripos(trim($components['having']),'having'))?' having ':'').$components['having'];
        }
        //去除order by
        $components['order'] = preg_replace_callback('|order\s*by|i',function(){return '';},$components['order']);

        //按照顺序连接，过滤掉一些特别的参数
        foreach($components as $key=>&$val){
            //$components得分顺序中join开始连接
            if(in_array($key,array('fields','order','top','distinct'))) continue;
            $sql .= " {$val} \r\n ";
        }

        $flag = true;//标记是否需要再次设置order by

        //是否偏移
        $offset = $components['offset'];
        $limit = $components['limit'];
        if(NULL !== $offset && NULL !== $limit){
            $outerOrder = ' order by ';
            if(!empty($components['order'])){
                //去掉其中的order by
                $orders = @explode(',',$components['order']);//分隔多个order项目

                foreach($orders as &$val){
                    $segs = @explode('.',$val);
                    $outerOrder .= array_pop($segs).',';
                }
                $outerOrder  = rtrim($outerOrder,',');
            }else{
                $outerOrder .= ' rand() ';
            }
            $endIndex = $offset+$limit;
            $sql = "SELECT T1.* FROM (
            SELECT  ROW_NUMBER() OVER ( {$outerOrder} ) AS ROW_NUMBER,thinkphp.* FROM ( {$sql} ) AS thinkphp
            ) AS T1 WHERE (T1.ROW_NUMBER BETWEEN 1+{$offset} AND {$endIndex} )";
            $flag = false;
        }
        if($flag && !empty($components['order'])){
            $sql .= ' order by '.$components['order'];
        }
        return $sql;
    }

}