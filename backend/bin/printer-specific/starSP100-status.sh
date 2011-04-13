#!/usr/bin/expect


# an automated script which logs into a printer, navigates the menu structure
# which displays the device status
#
# designed to work with:
# Star Line Printer TSP143 
# firmware 100.100
#
# march 2011 bas kamer


log_user 0

if {[llength $argv] < 3} {
    puts "usage: star-status.sh host user passw"
    exit 1
}


set timeout 2

set host [lindex $argv 0]
set user  [lindex $argv 1]
set password  [lindex $argv 2]

if [catch {
    spawn telnet "$host"

    expect "login:" { send "$user\r"; }
    expect "password:" { send "$password\r"; }


    expect "Enter Selection:" { send "96\r"; } \
        "Login incorrect" { exit 2; }

    expect "Enter Selection:" { send "4\r"; log_user 1; }


    # at this point the device status is displayed
    #[DEVICE STATUS]
    #ASB(HexDump)
    #[23 86 00 00 00 00 00 00  00 00 00 -- -- -- -- --]
    #[-- -- -- -- -- -- -- --  -- -- -- -- -- -- -- --]
    #[-- -- -- -- -- -- -- --  -- -- -- -- -- -- -- --]
    #[-- -- -- -- -- -- -- --  -- -- -- -- -- -- -- --]

    expect "Enter Selection:" { send "99\r"; }

    log_user 0;
    expect "Enter Selection:" { send "99\r"; }


} result] {
    puts "There was an error '$result'."
    exit 3;
} else {
}

