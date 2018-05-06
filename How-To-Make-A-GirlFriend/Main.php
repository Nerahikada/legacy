<?php

class GirlFriend{

    
    /**
     * @var  array  $children     
     * @var  string $name         GirlFriend's name
     * @var  string $anniversary  date when Couple was made [Year:Month:Day]
     * @var  string $birthday     GirlFriend's BirthDay     [Year:Month:Day]
     */

	public $children = [];
    public $name;
    public $anniversary;
    public $birthday;


	function __construct($name, $birthday)
    {
        $this->name = $name;
        $this->birthday = $birthday;
        $this->MakeCoupleEvent();
	}


	function MakeCoupleEvent()
    {
        $this->anniversary = date("Y:m:d");
	}


	function DestoryCoupleEvent()
    {
	}


	function MakeChildEvent()
    {
		$children[] = new Child();
	}
	

	function __destruct()
    {
	}

    /**
     * @return string 
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */

    public function getAge()
    {
        $ary = explode(":", $this->getBirthDay());
        $y = (int) $ary[0]; 
        $m = (int) $ary[1];
        $d = (int) $ary[2];
 
        $ny = (int) date('Y');
        $nm = (int) date('m');
        $nd = (int) date('d');

        $age = $ny - $y;
        if($m <= $nm && $d <= $nd){
            ++$age;
        }

        return $age;
    }


    public function getAnniv()
    {
        return $this->anniversary;
    }


    public function getBirthDay()
    {
        return $this->birthday;
    }

}


class Child
{

	function __construct()
    {
	}

	function __destruct()
    {
	}

}


new GirlFriend();