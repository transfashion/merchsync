<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;


class SyncRV extends SyncBaseHemoving {

	const bool SKIP_SEND = false;
	const bool SKIP_RECV = false;
	const bool SKIP_POST = false;
	const bool SKIP_UNSEND = false;
	const bool SKIP_UNRECV = false;
	const bool SKIP_UNPOST = false;

	const bool UNIMPLEMENTED_SEND = true;
	const bool UNIMPLEMENTED_RECV = true;
	const bool UNIMPLEMENTED_POST = true;
	const bool UNIMPLEMENTED_UNSEND = true;
	const bool UNIMPLEMENTED_UNRECV = true;
	const bool UNIMPLEMENTED_UNPOST = true;

	function __construct() {
		parent::__construct();
	}


	public function Send(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'RV-SEND';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/gethemoving.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$hemoving_id = $data['header']['hemoving_id'];
			$this->deleteHemovingData($hemoving_id);
			$this->copyToTempHemovingHeader($hemoving_id, $data['header']);
			$this->copyToTempHemovingDetil($hemoving_id, $data['items']);


			if (self::UNIMPLEMENTED_SEND) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function Recv(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'RV-RECV';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/gethemoving.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$hemoving_id = $data['header']['hemoving_id'];
			$this->updateTempHemovingHeader($hemoving_id, $data['header']);
			$this->updateTempHemovingDetil($hemoving_id, $data['items']);

			if (self::UNIMPLEMENTED_RECV) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function Post(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'RV-POST';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/gethemoving.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$hemoving_id = $data['header']['hemoving_id'];
			$this->updateTempHemovingHeader($hemoving_id, $data['header']);
			$this->updateTempHemovingDetil($hemoving_id, $data['items']);


			if (self::UNIMPLEMENTED_POST) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function UnSend(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'RV-UNSEND';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/gethemoving.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$hemoving_id = $data['header']['hemoving_id'];
			$this->deleteHemovingData($hemoving_id);

			if (self::UNIMPLEMENTED_UNSEND) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function UnRecv(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'RV-UNRECV';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/gethemoving.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$hemoving_id = $data['header']['hemoving_id'];
			$this->updateTempHemovingHeader($hemoving_id, $data['header']);
			$this->updateTempHemovingDetil($hemoving_id, $data['items']);

			if (self::UNIMPLEMENTED_UNRECV) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function UnPost(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'RV-UNPOST';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/gethemoving.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$hemoving_id = $data['header']['hemoving_id'];
			$this->updateTempHemovingHeader($hemoving_id, $data['header']);
			$this->updateTempHemovingDetil($hemoving_id, $data['items']);

			if (self::UNIMPLEMENTED_UNPOST) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}



	
}