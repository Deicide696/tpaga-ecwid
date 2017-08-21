<?php

    require_once dirname(__FILE__)."/../db/connection.php";
	
	/**
	* Model class 
	*/
	class CreditCard extends DB
	{
		
		public $id = null;
		public $last_four = "";
        public $token = "";
        public $user_id = "";

		function __construct()
		{
			new DB();
			$this->table =  "credit_card";
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
	    public function getLastFour()
	    {
	        return $this->last_four;
	    }
        /**
         * @return mixed
         */
        public function getToken()
        {
            return $this->token;
        }
        /**
         * @return mixed
         */
        public function getUserId()
        {
            return $this->user_id;
        }
        /**
         * @return mixed
         */
        public function getCreated()
        {
            return $this->created;
        }
	    /**
	     * @return mixed
	     */
	    public function setLastFour($last_four)
	    {
	        return $this->last_four =  $last_four;
	    }
        /**
         * @return mixed
         */
        public function setToken($token)
        {
            return $this->token =  $token;
        }
        /**
         * @return mixed
         */
        public function setUserId($user_id)
        {
            return $this->user_id =  $user_id;
        }
        /**
         * @return mixed
         */
        public function setCreated($created)
        {
            return $this->created =  $created;
        }
}