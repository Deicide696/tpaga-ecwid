<?php 

	require "../db/connection.php";
	
	/**
	* Model class 
	*/
	class CreditCard extends DB
	{
		
		public $id = null;
		public $bin = "";
		public $franchise = "";
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
	    public function getBin()
	    {
	        return $this->bin;
	    }
	    /**
	     * @return mixed
	     */
	    public function getFranchise()
	    {
	        return $this->franchise;
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
	    public function setBin($bin)
	    {
	        return $this->bin =  $bin;
	    }
	    /**
	     * @return mixed
	     */
	    public function setFranchise($franchise)
	    {
	        return $this->franchise =  $franchise;
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