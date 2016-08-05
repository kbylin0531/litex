#!/bin/bash
echo -e "\n-------------------------------"
echo "-- The count of parameter is '$#';"
echo -e "-------------------------------\n"

var_count=0
if  test ${#} -gt ${var_count}
then 
	echo -e "\n-- Try using config '$1'\n"
	/usr/local/sphinx/bin/searchd -c $1
else
	echo -e "\n-- Using default sphinx.conf '/home/asus/sphinx/sphinx.conf';\n"
	/usr/local/sphinx/bin/searchd -c /home/asus/sphinx/sphinx.conf
fi
