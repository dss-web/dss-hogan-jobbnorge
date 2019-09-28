<?php
declare( strict_types = 1 );
require_once ABSPATH . WPINC . '/class-simplepie.php';

const SIMPLE_NAMESPACE_JOBBNORGE = 'https://export.jobbnorge.no/xml/';

if ( ! class_exists( 'Jobbnorge_Item' ) ) {

	class Jobbnorge_Item extends \SimplePie_Item {

		public function get_jn_createdon() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'createdon' );
			return $data[0]['data'] ?? '';
		}
		public function get_jn_deadline() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'deadline' );
			return $data[0]['data'] ?? '';
		}

		public function get_jn_title() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'title' );
			return $data[0]['data'] ?? '';
		}

		public function get_jn_positiontitle() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'positiontitle' );
			return $data[0]['data'] ?? '';
		}

		public function get_jn_employername() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'employername' );
			return $data[0]['data'] ?? '';
		}

		public function get_jn_departmentname() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'departmentname' );
			return $data[0]['data'] ?? '';
		}

		public function get_jn_leadtext() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'leadtext' );
			return $data[0]['data'] ?? '';
		}

		public function get_jn_location() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'location' );
			return $data[0]['data'] ?? '';
		}
		public function get_jn_text() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'text' );
			return $data[0]['data'] ?? '';
		}
		public function get_jn_refcode() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'refcode' );
			return $data[0]['data'] ?? '';
		}
		public function get_jn_jobscopee() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'jobscope' );
			return $data[0]['data'] ?? '';
		}
		public function get_jn_jobduration() : string {
			$data = $this->get_item_tags( SIMPLE_NAMESPACE_JOBBNORGE, 'jobduration' );
			return $data[0]['data'] ?? '';
		}
	}
}
