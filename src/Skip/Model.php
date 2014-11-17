<?php

    namespace Skip;

    class Model
    {
        const SORT_ASC = 'asc';
        const SORT_DESC = 'desc';

        protected $db;
        protected $pk = 'id';

        public function __construct( $conn )
        {
            $this->db = $conn;
        }
        
        // @Hmmm: use doctrine built in functions
        public function insert( $item_original, $table='' )
        {
            $_item = is_array($item_original)? $item_original : (array) $item_original;

            $item = array();
            foreach( $_item as $key => $value ) {
                if( substr( $key, 0, 2 ) !== '__' ) {
                    $item[$key] = $value;
                }
            }

            $fields_sql = array_keys($item);
            $table = $table? $table : $this->table;

            $sql = <<<EOF
INSERT INTO $table ( %s )
VALUES ( %s )
EOF;
            $sql = sprintf( $sql, '`'.implode('`, `', $fields_sql).'`', ':'.implode(', :', $fields_sql) );

            $stmt = $this->db->prepare( $sql );

            foreach( $item as $key => $value )
            {
                $stmt->bindValue( $key, $value );
            }

            $stmt->execute();
            return $this->db->lastInsertId();
        }

        public function update( $id, $data, $table='', $pk='', $return_qb=false) {
            $table = $table?: $this->table;
            $pk = $pk?: $this->pk;

            $qb = $this->db->createQueryBuilder();
            $qb->update( $table )
                ->where( '`'.$pk.'` = :id')
                ->setParameter('id', $id);

            $data_array = is_array($data)? $data : (array) $data;

            foreach( $data_array as $key => $value ) {
                if( substr( $key, 0, 2 ) !== '__' ) {
                    $placeholder = ":$key";
                    $qb->set( "`$key`", $placeholder)
                        ->setParameter( $key, $value );
                }
            }

            return $qb->execute();
        }

        public function get($id, $table='', $pk='', $return_qb=false) {
            $table = $table?: $this->table;
            $pk = $pk?: $this->pk;

            $qb = $this->db->createQueryBuilder()
                ->from( $table, 't' )
                ->select('*')
                ->where('t.' . $pk . ' = :id')
                ->setParameter('id', $id);
            return $return_qb? $qb : $qb->execute()->fetch();
        }

        public function delete($id, $table='', $pk='', $return_qb=false) {
            $table = $table?: $this->table;
            $pk = $pk?: $this->pk;

            $qb = $this->db->createQueryBuilder()
                ->delete( $table )
                ->where($pk.' = :id')
                ->setParameter('id', $id);
            return $return_qb? $qb : $qb->execute();
        }

        public function dateDelete($id, $table='', $pk='', $return_qb=false) {
            $table = $table?: $this->table;
            $pk = $pk?: $this->pk;

            $qb = $this->db->createQueryBuilder()
                ->update( $table )
                ->where( $pk.' = :id')
                ->setParameter('id', $id)

                ->set('deleted', ':deleted')
                ->setParameter('deleted', date('Y-m-d H:i:s'));

            return $return_qb? $qb : $qb->execute();
        }

        public function isDateDeleted($id, $table='', $pk='', $return_qb=false) {
            $table = $table?: $this->table;
            $pk = $pk?: $this->pk;

            $qb = $this->db->createQueryBuilder()
                ->from( $table, 't' )
                ->select('COUNT(*) c' )
                ->where($pk.' = :id')
                ->andWhere("deleted > '0000-00-00 00:00:00'")
                ->setParameter('id', $id);
            if( $return_qb ) {
                return $qb;
            } else {
                return $qb->execute()->fetchColumn()? true : false;
            }
        }

        public function isDeleted($id, $table='', $pk='', $return_qb=false) {
            $table = $table?: $this->table;
            $pk = $pk?: $this->pk;

            $qb = $this->db->createQueryBuilder()
                ->from( $table, 't' )
                ->select('COUNT(*) c' )
                ->where($pk.' = :id')
                ->setParameter('id', $id);
            if( $return_qb ) {
                return $qb;
            } else {
                return $qb->execute()->fetchColumn()? false : true;
            }
        }

        public function getAll($table='', $return_qb=false) {
            $table = $table?: $this->table;

            $qb = $this->db->createQueryBuilder()
                ->from( $table, 't' )
                ->select('*');
            return $return_qb? $qb : $qb->execute()->fetchAll();
        }

    }