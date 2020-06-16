<?PHP

class skel {
	var $ep; // Where the EasyPage parent link is stored

	public function __construct($ep) {
		$this->ep = &$ep; // Recursively store the EP obj
	}

}
