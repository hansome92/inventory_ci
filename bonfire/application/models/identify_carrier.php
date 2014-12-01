<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class identify_carrier extends BF_Model {
		
	public function __construct()
	{
	}
	
	public function check_carrier($tracking_number)
	{
		if($this -> checkups($tracking_number)==0)
		{
			if($this -> checkfedex($tracking_number)==0)
			{
				if($this -> checkfedexg($tracking_number)==0)
				{
					if($this -> checkusps($tracking_number)==0)
					{
						if($this -> checkuspsexpmail($tracking_number)==0)
							return false;
						else
							return "USPS";
					}
					else
						return "USPS";
				}
				else
					return "FedEx";
			}
			else
				return " FedEx";
		}
		else{
			return "UPS";
		}
	}
	
	public function checkfedex($no)
	{
		if(strlen($no)!=12)
			return 0;
		else
		{
			$temp=substr($no,0,11);
			$multiplier=7;
			$sum=0;
			for($i=10;$i>=0;$i--)
			{
				if($multiplier==7)
					$multiplier=1;
				else if($multiplier==1)
					$multiplier=3;
				else if($multiplier==3)
					$multiplier=7;			
				$sum+=$multiplier * (int)(substr($temp,$i,1));
			}
			$rem=$sum%11;
			if($rem==10)
				$rem=0;
			if((int)(substr($no,11,1))==$rem)
				return 1;
			else
				return 0;
		}
	}
	public function checkups($no)
	{
		$upschar2num=array("A"=>"2","B"=>"3","C"=>"4","D"=>"5","E"=>"6","F"=>"7","G"=>"8","H"=>"9","I"=>"0","J"=>"1","K"=>"2","L"=>"3","M"=>"4","N"=>"5","O"=>"6","P"=>"7","Q"=>"8","R"=>"9","S"=>"0","T"=>"1","U"=>"2","V"=>"3","W"=>"4","X"=>"5","Y"=>"6","Z"=>"7");
		if(strlen($no)!=18)
			return 0;
		else
		{
			$temp=substr($no,0,2);
			if($temp!="1Z")
				return 0;
			else
			{
				$temp=substr($no,2,15);
				$sumodd=0;
				$sumeven=0;
				for($i=0;$i<15;$i++)
				{	
					$tempnum=strtoupper(substr($temp,$i,1));
					if(array_key_exists($tempnum,$upschar2num))
					{
						$tempnum=$upschar2num[$tempnum];
					}
					if($i%2==0)
						$sumodd+=(int)($tempnum);
					else
						$sumeven+=(int)($tempnum);
				}
				$sum=$sumodd + 2*$sumeven;
				if((int)(substr($no,17,1))==(10-($sum%10))|| ($sum%10==0))
					return 1;
				else
					return 0;
			}
		}
	}
	
	public function checkfedexg($no)
	{
		if((strlen($no)!=22) &&  (strlen($no)!=15))
			return 0;
		else
		{
			$temp="";
			if(strlen($no)==22)
			{
				if((int)substr($no,0,2)==96)
					$temp=substr($no,7);
				else
				{
					$temp="12";
				}
			}
			$temp=trim($temp);
			$len=strlen($temp);
			$chk=substr($temp,$len-1,1);
			$temp=substr($temp,0,$len-1);
			$len=strlen($temp);
			$sumodd=0;
			$sumeven=0;		
			$sum=0;
			for($i=$len-1;$i>=0;$i--)
			{
				$tempnum=substr($temp,$i,1);
				if($i%2==0)
					$sumodd+=(int)($tempnum);
				else
					$sumeven+=(int)($tempnum);
			}
			$sum=$sumodd + (3*$sumeven);
			if((int)($chk)==(10-($sum%10))|| ($sum%10==0))
				return 1;
			else
				return 0;
		}
	}

	public function checkusps($no)
	{
		if(strlen($no)!=22 && strlen($no)!=20 && strlen($no)!=13)
			return 0;
		else
		{
			if(strlen($no)==13)
			{
				if(strtoupper(substr($no,-2))=="US")
					$temp=substr($no,2,9);
				else
					return 0;
			}
			else
				$temp=$no;
						
			$temp=$no;
			$temp=trim($temp);
			$len=strlen($temp);
			$chk=substr($temp,$len-1,1);
			$temp=substr($temp,0,$len-1);
			$len=strlen($temp);
			$sumodd=0;
			$sumeven=0;		
			$sum=0;
			for($i=$len-1;$i>=0;$i--)
			{
				$tempnum=substr($temp,$i,1);
				if($i%2==0)
					$sumeven+=(int)($tempnum);
				else
					$sumodd+=(int)($tempnum);
			}
			$sum=$sumodd + (3*$sumeven);
			if((int)($chk)==(10-($sum%10))|| ($sum%10==0))
				return 1;
			else
				return 0;
		}
	}

	public function checkuspsexpmail($no)
	{
		if(strlen($no)!=13)
			return 0;
		else
		{
			if(strtoupper(substr($no,-2))=="US")
				$temp=substr($no,2,9);
			else
				return 0;
			$temp=trim($temp);
			$len=strlen($temp);
			$chk=substr($temp,$len-1,1);
			$temp=substr($temp,0,$len-1);
			$len=strlen($temp);
			$sum=0;
			$sum+=8*(int)substr($temp,0,1);
			$sum+=6*(int)substr($temp,1,1);
			$sum+=4*(int)substr($temp,2,1);
			$sum+=2*(int)substr($temp,3,1);
			$sum+=3*(int)substr($temp,4,1);
			$sum+=5*(int)substr($temp,5,1);
			$sum+=9*(int)substr($temp,6,1);
			$sum+=7*(int)substr($temp,7,1);
			$rem=$sum%11;
			if($rem==0)
				$rem=5;
			else if($rem==1)
				$rem=0;
			else
				$rem=11-$rem;
			if((int)($chk)==$rem)
				return 1;
			else
				return 0;
		}
	}

}