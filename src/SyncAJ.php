<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;


class SyncAJ extends SyncBaseHemoving {

	const bool SKIP_POSTALL = false;

	const bool UNIMPLEMENTED_POSTALL = true;

	function __construct() {
		parent::__construct();
	}

	public function PostAll(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'AJ-POSTALL';

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

			if (self::UNIMPLEMENTED_POSTALL) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}
}