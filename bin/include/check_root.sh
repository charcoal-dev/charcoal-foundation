#!/bin/bash
if [ "$HOST_UID" -eq 0 ]; then
  echo -e "\e[31mERROR:\e[0m Cannot run this command as \"\e[31mroot\e[0m\" user";
  exit
fi