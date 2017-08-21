<?php
require_once dirname(__FILE__)."/../db/connection.php";
	
	/**
	* Model class 
	*/
	class User extends DB
	{
		public $id = null;
		public $customer_id_ecwid = "";
		public $token_tpaga = "";

		function __construct()
		{
			new DB();
			$this->table =  "user";
		}
		
	    /**
	     * @return mixed
	     */
	    public function getId()
	    {
	        return $this->id;
	    }
	    /**
	     * @return mixed
	     */
	    public function getCustomerIdEcwid()
	    {
	        return $this->customer_id_ecwid;
	    }
	    /**
	     * @return mixed
	     */
	    public function getTokenTpaga()
	    {
	        return $this->token_tpaga;
	    }
	    /**
	     * @return mixed
	     */
	    public function setCustomerIdEcwid($customer_id_ecwid)
	    {
	        return $this->customer_id_ecwid =  $customer_id_ecwid;
	    }
	    /**
	     * @return mixed
	     */
	    public function setTokenTpaga($token_tpaga)
	    {
	        return $this->token_tpaga =  $token_tpaga;
	    }
    }

?>