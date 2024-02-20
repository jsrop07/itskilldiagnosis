<?php
namespace Batch\Model;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;


class PushTable {

    public function __construct()
    {
        $config=\Custom\Session\UserFunc::getConfig();
        $dbArr=$config['db'];
        $this->adapter = new Adapter($dbArr);
        $this->sql=new Sql($this->adapter);
        $this->config=$config;
    }


	public function ronbunLogger(){
		$sql = new Sql($this->tableGateway->getAdapter());

		for($i=7;$i>0;$i--){
			$today_start=strtotime(date("Y-m-d",time()-$i*60*60*24));
			$today_end=$today_start+60*60*24-1;
			$d_start=date("Y-m-d H:i:s",$today_start);
			$d_end=date("Y-m-d H:i:s",$today_end);

			$data=$sql->select('tb_counter_ronbun');
			$data->columns(array(
				new Expression('left(tb_counter_ronbun.cur_date,10) as cur_date'),
				new Expression('tb_counter_ronbun.ronbun_id as ronbun_id'),
				new Expression('sum(view_cnt) as view_cnt'),
			));
			$data->group(new Expression('left(cur_date,10)'));
			$data->where(new Expression('view_cnt!="0"'));
			$data->group(new Expression('ronbun_id'));
			$data->where(new Expression('cur_date>="'.$d_start.'" and cur_date<="'.$d_end.'"'));
			$r_data=$sql->prepareStatementForSqlObject($data)->execute();

			$filename="RONBUN_WEB_".date("Ymd",$today_start).".csv";
			$root=str_replace("/public/index.php","",$_SERVER['PHP_SELF']);
			$CSVROOT=$root."/data/uploads/csv/";

			if($r_data->count()!=0){
				$f=fopen($CSVROOT.$filename,'w+');
				foreach($r_data as $row){
					$ret_1=substr($row['ronbun_id'],0,4);
					$ret_2=substr($row['ronbun_id'],4,7);
					$ret_3=substr($row['ronbun_id'],11,4);
					$ret_4=substr($row['ronbun_id'],15,4);
					$ret_5=substr($row['ronbun_id'],19,4);
					fwrite($f,$ret_1."_".$ret_2."_".$ret_3."_".$ret_4."_".$ret_5.",".$row['view_cnt']."\n");
				}
				fclose($f);
			}

			$data=$sql->select('tb_counter_ronbun_uniqueuser');
			$data->columns(array(
				new Expression('left(tb_counter_ronbun_uniqueuser.cur_date,10) as cur_date'),
				new Expression('tb_counter_ronbun_uniqueuser.ronbun_id as ronbun_id'),
				new Expression('sum(download_cnt) as download_cnt'),
			));
			$data->group(new Expression('left(cur_date,10)'));
			$data->where(new Expression('download_cnt!="0"'));
			$data->group(new Expression('ronbun_id'));
			$data->where(new Expression('cur_date>="'.$d_start.'" and cur_date<="'.$d_end.'"'));
			$r_data=$sql->prepareStatementForSqlObject($data)->execute();

			$filename="RONBUN_APL_".date("Ymd",$today_start).".csv";
			$root=str_replace("/public/index.php","",$_SERVER['PHP_SELF']);
			$CSVROOT=$root."/data/uploads/csv/";

			if($r_data->count()!=0){
				$f=fopen($CSVROOT.$filename,'w+');
				foreach($r_data as $row){
					$ret_1=substr($row['ronbun_id'],0,4);
					$ret_2=substr($row['ronbun_id'],4,7);
					$ret_3=substr($row['ronbun_id'],11,4);
					$ret_4=substr($row['ronbun_id'],15,4);
					$ret_5=substr($row['ronbun_id'],19,4);
					fwrite($f,$ret_1."_".$ret_2."_".$ret_3."_".$ret_4."_".$ret_5.",".$row['download_cnt']."\n");
				}
				fclose($f);
			}
		}
	}



	
}