#!/bin/bash
virtualmin list-domains --name-only | xargs -i ./brutal_virtualmin_accounts_list_domain.sh "{}" 

