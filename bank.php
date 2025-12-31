<?php

require_once('common.php');

page_header('bank.title');

output('bank.header');
$op = httpget('op');
switch ($op) {
    case 'deposit':
        $balanceMsg = loadTranslation('bank.current_balance');
        $debtMsg = loadTranslation('bank.current_debt');
    	output_notl(
            $session['user']['goldinbank'] >= 0 ? $balanceMsg : $debtMsg,
            abs($session['user']['goldinbank'])
        );
    	output('bank.current_on_hand', $session['user']['gold']);
    	output('bank.forms.deposit', true);
        addnav('', 'bank.php?op=depositfinish');
        break;
    case 'depositfinish':
    	$amount = abs((int)httppost('amount'));
    	if ($amount == 0 || $amount > $session['user']['gold']) {
    		$amount = $session['user']['gold'];
    	}
    	$payoffDebt = loadTranslation('bank.payoff_debt');
    	$depositedGold = loadTranslation('bank.deposited_gold');
    	debuglog("deposited $amount gold in the bank");
    	$session['user']['goldinbank'] += $amount;
    	$session['user']['gold'] -= $amount;
    	output_notl(
            $session['user']['goldinbank'] >= 0 ? $depositedGold : $payoffDebt,
            $amount,
            $session['user']['name'],
            abs($session['user']['goldinbank']),
            $session['user']['gold'],
        );
        break;
    case 'withdraw':
    	$balance = loadTranslation('bank.current_balance');
    	$debt = loadTranslation('bank.current_debt');
    	output_notl(
            $session['user']['goldinbank'] >= 0 ? $balance : $debt,
            abs($session['user']['goldinbank'])
        );
    	output('bank.forms.withdraw', true);
    	addnav('', 'bank.php?op=withdrawfinish');
        break;
    case 'withdrawfinish':
    	$amount = abs((int) httppost('amount'));
    	if ($amount == 0 || $amount > $session['user']['goldinbank']) {
    		$amount = abs($session['user']['goldinbank']);
    	}
    	$session['user']['goldinbank'] -= $amount;
    	$session['user']['gold'] += $amount;
    	debuglog("withdrew $amount gold from the bank");
    	output(
            'bank.withdrew_gold',
            $amount,
            $session['user']['name'],
            abs($session['user']['goldinbank']),
            $session['user']['gold']
        );
        break;
    default: 
        checkday();
        output(
            'bank.default',
            $session['user']['name']
        );
        if ($session['user']['goldinbank'] >= 0) {
        	output(
                'bank.balance_positive',
                abs($session['user']['goldinbank'])
            );
        }
        else {
        	output(
                'bank.balance_negative',
                abs($session['user']['goldinbank'])
            );
        }
        break;
}

villagenav();
addnav('bank.nav_headers.main');
addnav('bank.navs.withdraw', 'bank.php?op=withdraw');
addnav('bank.navs.deposit', 'bank.php?op=deposit');

page_footer();
