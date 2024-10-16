<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;


class SyncTR extends SyncBaseHemoving {

	const bool SKIP_PROP = false;
	const bool SKIP_SEND = false;
	const bool SKIP_RECV = false;
	const bool SKIP_UNPROP = false;
	const bool SKIP_UNSEND = false;
	const bool SKIP_UNRECV = false;

	const bool UNIMPLEMENTED_PROP = true;
	const bool UNIMPLEMENTED_SEND = true;
	const bool UNIMPLEMENTED_RECV = true;
	const bool UNIMPLEMENTED_UNPROP = true;
	const bool UNIMPLEMENTED_UNSEND = true;
	const bool UNIMPLEMENTED_UNRECV = true;

	function __construct() {
		parent::__construct();
	}

	public function Prop(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'TR-PROP';

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

			if (self::UNIMPLEMENTED_PROP) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function Send(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'TR-SEND';

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

			if (self::UNIMPLEMENTED_SEND) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function Recv(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'TR-RECV';

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

	public function UnProp(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'TR-UNPROP';

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

			if (self::UNIMPLEMENTED_UNPROP) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function UnSend(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'TR-UNSEND';

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

			if (self::UNIMPLEMENTED_UNSEND) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function UnRecv(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'TR-UNRECV';

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
}