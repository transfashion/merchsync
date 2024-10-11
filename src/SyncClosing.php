<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;


class SyncClosing extends SyncBase {

	const bool SKIP_CLOSE = false;
	const bool SKIP_OPEN = false;

	const bool UNIMPLEMENTED_CLOSE = true;
	const bool UNIMPLEMENTED_OPEN = true;

	function __construct() {
		parent::__construct();
	}

	public function Close(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'INV-CLOSE';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/getclosing.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$closing_id = $data['header']['closing_id'];
			


			if (self::UNIMPLEMENTED_CLOSE) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

	public function Open(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'INV-OPEN';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/getclosing.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$closing_id = $data['header']['closing_id'];

			if (self::UNIMPLEMENTED_OPEN) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

}