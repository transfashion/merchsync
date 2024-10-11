<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

class Sync {
	const _DELAY_BETWEEN_LOOP = 0; // seconds
	const _MAX_TX_PER_LOOP = 10;



	private static object $stmt_set_batch;
	private static object $stmt_set_batch_processing;
	private static object $stmt_set_result;
	private static object $stmt_set_completed;


	private static object $stmt_get_batch_unprocessed;
	private static object $stmt_get_batch_queue;
	
	
	private static object $stmt_get_fail_number;
	private static string $sql_cond_unprocessed;



	public static function main() : void {	
		try {
			Log::info('Sync Process starting');
			Database::Connect();

			self::$sql_cond_unprocessed = "
				    (merchsync_isfail<3 or merchsync_batch is null) 
				and (merchsync_result<>'SKIP' or merchsync_result is null)
				-- and merchsync_type LIKE 'SL%' 
			";
			

			$progList = self::getSyncProgList();

			self::RemoveCompleted();
			self::ResetTimeout();
			self::ResetSkipped();


			$batch_id = uniqid();
			$txcount = self::CreateSyncBatch($batch_id, self::_MAX_TX_PER_LOOP);
			while ($txcount>0) {
				$rows = self::GetQueues($batch_id);

				foreach ($rows as $row) {
					$merchsync_id = $row['merchsync_id'];
					$merchsync_doc = $row['merchsync_doc'];
					$merchsync_type = $row['merchsync_type'];

					$prog = $progList[$merchsync_type];
					$instance = $prog['instance'];
					$methodname = $prog['method'];
					$skip = $prog['skip'];

					if ($skip) {
						self::SetSkipped($merchsync_id);
						Log::info("Syncing $merchsync_id $merchsync_type $merchsync_doc ...SKIP");
						continue;
					} else {
						Log::info("Syncing $merchsync_id $merchsync_type $merchsync_doc ...");
						self::SetProcessingFlag($merchsync_id, 1);
					}
				
				
					if (!array_key_exists($merchsync_type, $progList)) {
						$msg = "Sync untuk '$merchsync_type' belum didefinisikan";
						self::SetResult($merchsync_id, 1, $msg);
						throw new \Exception($msg);
					}

					if (!method_exists($instance, $methodname)) {
						$msg = "Method '$methodname' belum didefinisikan untuk '$merchsync_type'";
						self::SetResult($merchsync_id, 1, $msg);
						throw new \Exception($msg);
					}

					try {
						$instance->{$methodname}($merchsync_id, $merchsync_doc, $merchsync_type);
						self::SetCompleted($merchsync_id);
						self::SetResult($merchsync_id, 0, "");
					} catch (\Exception $ex) {
						self::SetResult($merchsync_id, 1, $ex->getMessage());
					}
					
				}

				// hapus queue yang telah selesai diproses
				self::RemoveCompleted();
			
				// ambil lagi queue untuk diproses pada loop berikutnya
				$batch_id = uniqid();
				$txcount = self::CreateSyncBatch($batch_id, self::_MAX_TX_PER_LOOP);


				if (self::_DELAY_BETWEEN_LOOP > 0) {
					sleep(self::_DELAY_BETWEEN_LOOP);
				}
				

			}


			Log::info('DONE.');
		} catch (\Exception $e) {
			Log::error('PROCESS ERROR! ' . $e->getMessage());
			throw $e;
		}
	}


	private static function getSyncProgList() : array {
		$syncSales = new SyncSales();
		$syncReg = new SyncRegister(); 
		$syncDO = new SyncDO(); 
		$syncAJ = new SyncAJ(); 
		$syncTR = new SyncTR; 
		$syncRV = new SyncRV; 
		$syncPricing = new SyncPricing();
		$syncClosing = new SyncClosing();



		return [
			'SL' => ['instance'=>$syncSales, 'method'=>'Sync', 'skip'=>SyncSales::SKIP_SYNC],
			'REG' => ['instance'=>$syncReg, 'method'=>'Sync', 'skip'=>SyncRegister::SKIP_SYNC],
			'DO-POSTALL'  => ['instance'=>$syncDO, 'method'=>'PostAll', 'skip'=>SyncDO::SKIP_POSTALL],
			'AJ-POSTALL' => ['instance'=>$syncAJ, 'method'=>'PostAll', 'skip'=>SyncAJ::SKIP_POSTALL],
			'TR-PROP' => ['instance'=>$syncTR, 'method'=>'Prop', 'skip'=>SyncTR::SKIP_PROP],
			'TR-SEND' => ['instance'=>$syncTR, 'method'=>'Send', 'skip'=>SyncTR::SKIP_SEND],
			'TR-RECV' => ['instance'=>$syncTR, 'method'=>'Recv', 'skip'=>SyncTR::SKIP_RECV],
			'TR-UNPROP' => ['instance'=>$syncTR, 'method'=>'UnProp', 'skip'=>SyncTR::SKIP_UNPROP],
			'TR-UNSEND' => ['instance'=>$syncTR, 'method'=>'UnSend', 'skip'=>SyncTR::SKIP_UNSEND],
			'TR-UNRECV' => ['instance'=>$syncTR, 'method'=>'UnRecv', 'skip'=>SyncTR::SKIP_UNRECV],
			'RV-SEND' => ['instance'=>$syncRV, 'method'=>'Send', 'skip'=>SyncRV::SKIP_SEND],
			'RV-RECV' => ['instance'=>$syncRV, 'method'=>'Recv', 'skip'=>SyncRV::SKIP_RECV],
			'RV-POST' => ['instance'=>$syncRV, 'method'=>'Post', 'skip'=>SyncRV::SKIP_POST],
			'RV-UNSEND' => ['instance'=>$syncRV, 'method'=>'UnSend', 'skip'=>SyncRV::SKIP_UNSEND],
			'RV-UNRECV' => ['instance'=>$syncRV, 'method'=>'UnRecv', 'skip'=>SyncRV::SKIP_UNRECV],
			'RV-UNPOST' => ['instance'=>$syncRV, 'method'=>'UnPost', 'skip'=>SyncRV::SKIP_UNPOST],
			'PRC' => ['instance'=>$syncPricing, 'method'=>'Sync', 'skip'=>SyncPricing::SKIP_SYNC],
			'INV-CLOSE' => ['instance'=>$syncClosing, 'method'=>'Close', 'skip'=>SyncClosing::SKIP_CLOSE],
			'INV-OPEN' => ['instance'=>$syncClosing, 'method'=>'Open', 'skip'=>SyncClosing::SKIP_OPEN],

		];
	}


	/*
	hapus queue yang sudah selesai (cek field merchsync_iscompleted=1)
	agar tidak perlu diexekusi lagi pada proses berikutnya 
	*/
	private static function  RemoveCompleted() : void {
		Log::info("Remove Completed Queue");

		try {
			$sql = "delete from fsn_merchsync where merchsync_iscompleted=1";
			Database::$DbMain->exec($sql);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


	/*
	reset merchsync_batch dan isprocessing queue yang telah timeout
	*/	
	private static function ResetTimeout() : void {
		Log::info("Reset Timeout Queue");

		try {
			$sql = "
				update fsn_merchsync
				set 
				merchsync_batch=null
				where
				merchsync_timeout > now()
			";
			Database::$DbMain->exec($sql);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private static function SetSkipped(string $merchsync_id) : void {
		try {
			$sql = "
				update fsn_merchsync
				set 
					merchsync_isprocessing = 0,
					merchsync_timeout = now(),
					merchsync_result = 'SKIP'
				where
					merchsync_id = :merchsync_id
			";
			$stmt = Database::$DbMain->prepare($sql);
			$stmt->execute([
				':merchsync_id' => $merchsync_id
			]);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


	/*
	reset merchsync_batch dan isprocessing queue yang telah timeout
	*/
	private static function ResetSkipped() : void {
		Log::info("Reset Skipped Queue");

		try {
			$sql = "
				update fsn_merchsync
				set 
				merchsync_timeout=null,
				merchsync_batch=null,
				merchsync_result=null
				where
				    merchsync_result='SKIP'
				or (merchsync_isprocessing=0 and merchsync_batch is not null and merchsync_iscompleted=0)
			";
			Database::$DbMain->exec($sql);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


	/*
	ambil transaksi sejumlah $maxtx yang belum di synkronisasi, 
	kembalikan jumlah yang ditemukan 
	*/
	private static function CreateSyncBatch(string $batch_id, int $maxtx) : int {
		Log::info("Get UnProcessed Queue ...");


		try {

			if (!isset(self::$stmt_set_batch)) {
				$sqlupd = "
					update fsn_merchsync
					set 
						merchsync_batch = :batch
					where
						merchsync_id = :merchsync_id
				";
				$stmt = Database::$DbMain->prepare($sqlupd);
				self::$stmt_set_batch = $stmt;
			}
			

			if (!isset(self::$stmt_get_batch_unprocessed)) {
				$unprocessed_cond = self::$sql_cond_unprocessed;
				$sql = "
					select *
					from fsn_merchsync 
					where  
					$unprocessed_cond
					order by _createby asc limit $maxtx
				";
				$stmt = Database::$DbMain->prepare($sql);
				self::$stmt_get_batch_unprocessed = $stmt;
			}

			$stmt = self::$stmt_get_batch_unprocessed;
			$stmt->execute();
			$rows =$stmt->fetchAll();
			$count = count($rows);

			Log::info("Found " . $count . " data.");
			foreach ($rows as $row) {
				$merchsync_id = $row['merchsync_id'];
				$stmt = self::$stmt_set_batch;
				$stmt->execute([
					':batch' => $batch_id,
					':merchsync_id' => $merchsync_id
				]);
			}

			return $count;
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


	private static function  GetQueues(string $batch_id) : array {
		try {
			if (!isset(self::$stmt_get_batch_queue)) {
				$sql = "
					select * from fsn_merchsync where merchsync_batch = :batch order by _createby asc
				";
				$stmt = Database::$DbMain->prepare($sql);
				self::$stmt_get_batch_queue = $stmt;
			}
			$stmt = self::$stmt_get_batch_queue;
			$stmt->execute([':batch' => $batch_id]);
			return $stmt->fetchAll();
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


	private static function SetProcessingFlag(string $merchsync_id, int $isprocessing) : void {
		try {
			if (!isset(self::$stmt_set_batch_processing)) {
				$sql = "
					update fsn_merchsync
					set 
						merchsync_isprocessing = :isprocessing,
						merchsync_timeout = now() + INTERVAL 10 minute
					where
						merchsync_id = :merchsync_id
				";
				$stmt = Database::$DbMain->prepare($sql);
				self::$stmt_set_batch_processing = $stmt;
			}
			
			$stmt =self::$stmt_set_batch_processing;
			$stmt->execute([
				':merchsync_id' => $merchsync_id,
				':isprocessing' => $isprocessing
			]);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}




	private static function SetResult(string $merchsync_id, int $isfail, string $msg) : void {
		try {
			
			$fail_attempt = 0;
			if ($isfail == 1) {
				$fail_attempt = 1;
				if (!isset(self::$stmt_get_fail_number)) {
					$sqlgetfail = "select merchsync_isfail from fsn_merchsync where merchsync_id = :merchsync_id";
					self::$stmt_get_fail_number = Database::$DbMain->prepare($sqlgetfail);
				}
				self::$stmt_get_fail_number->execute([':merchsync_id' => $merchsync_id]);
				$row = self::$stmt_get_fail_number->fetch();
				$fail_attempt = $row['merchsync_isfail'] + 1;
			}


			if (!isset(self::$stmt_set_result)) {
				$sql = "
					update fsn_merchsync
					set 
						merchsync_isfail = :isfail,
						merchsync_result = :msg
					where
						merchsync_id = :merchsync_id
				";
				$stmt = Database::$DbMain->prepare($sql);
				self::$stmt_set_result = $stmt;
			}
			
			$stmt = self::$stmt_set_result;
			$stmt->execute([
				':merchsync_id' => $merchsync_id,
				':isfail' => $fail_attempt,
				':msg' => substr($msg, 0, 255), // $msg
			]);
		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}

	private static function SetCompleted(string $merchsync_id) : void {
		try {
			if (!isset(self::$stmt_set_completed)) {
				$sql = "
					update fsn_merchsync
					set 
						merchsync_isprocessing = 0,
						merchsync_iscompleted = 1
					where
						merchsync_id = :merchsync_id
				";
				$stmt = Database::$DbMain->prepare($sql);
				self::$stmt_set_completed = $stmt;
			}
			
			$stmt = self::$stmt_set_completed;
			$stmt->execute([
				':merchsync_id' => $merchsync_id
			]);

		} catch (\Exception $ex) {
			Log::error($ex->getMessage());
			throw $ex;
		}
	}


}