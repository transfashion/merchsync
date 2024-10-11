<?php namespace TransFashion\MerchSync;

use AgungDhewe\PhpLogger\Log;

use AgungDhewe\PhpSqlUtil\SqlDelete;
use AgungDhewe\PhpSqlUtil\SqlInsert;
use AgungDhewe\PhpSqlUtil\SqlUpdate;


class SyncPricing extends SyncBase {

	const bool SKIP_SYNC = false;

	const bool UNIMPLEMENTED_SYNC = true;

	function __construct() {
		parent::__construct();
	}

	public function Sync(string $merchsync_id, string $merchsync_doc, string $merchsync_type ) : void {
		$currentSyncType = 'PRC';

		try {
			// ambil data dari URL
			$id = $merchsync_doc;
			if ($merchsync_type!=$currentSyncType) {
				throw new \Exception("Type Sync $merchsync_type tidak sesuai dengan $currentSyncType"); 
			}

			$endpoint = $this->TransBrowserUrl . '/getpricing.php?id='. $id;
			$data = $this->getDataFromUrl($endpoint);

			$price_id = $data['header']['price_id'];
			// $this->deleteHemovingData($hemoving_id);
			// $this->copyToTempHemovingHeader($data['header']);
			// $this->copyToTempHemovingDetil($data['items']);

			if (self::UNIMPLEMENTED_SYNC) {
				throw new \Exception("[$currentSyncType] not full implemented" );
			}
		} catch (\Exception $ex) {
			Log::warning($ex->getMessage());
			throw $ex;
		}
	}

}