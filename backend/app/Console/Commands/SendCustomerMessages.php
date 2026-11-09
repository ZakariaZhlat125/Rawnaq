<?php
namespace App\Console\Commands;

use App\Services\CustomerMessages;
use App\Services\TaqnyatSmsService;
use Illuminate\Console\Command;

class SendCustomerMessages extends Command
{
    protected $signature = 'customers:send-messages';
    protected $description = 'Send WhatsApp birthday greetings to customers whose birthday is today';

    public function handle(CustomerMessages $messages): int
    {
        $campaignId = $messages->birthdays();
        $count = $campaignId === null ? 0 : $messages->sendCampaign($campaignId, app(TaqnyatSmsService::class));
        $this->info('Birthday messages processed: '.$count);
        return self::SUCCESS;
    }
}
