<?php

declare(strict_types=1);

use DbChatbot\Chat\Tools\CustomerSummaryTool;
use DbChatbot\Chat\Tools\ExportReportTool;
use DbChatbot\Chat\Tools\GetOrderTool;
use DbChatbot\Chat\Tools\HelpTool;
use DbChatbot\Chat\Tools\LowStockTool;
use DbChatbot\Chat\Tools\MyOrdersTool;
use DbChatbot\Chat\Tools\SalesSummaryTool;
use DbChatbot\Chat\Tools\SearchOrdersTool;

return [

    /*
    |--------------------------------------------------------------------------
    | Chat driver
    |--------------------------------------------------------------------------
    |
    | "rules" matches phrases to tools. An LLM driver can be added later by
    | implementing DbChatbot\Chat\Drivers\ChatDriver and mapping it here.
    | Tools stay the same either way.
    |
    */

    'driver' => env('CHATBOT_DRIVER', 'rules'),

    'layout' => 'app-layout',

    'max_message_length' => (int) env('CHATBOT_MAX_MESSAGE_LENGTH', 500),

    'report_url_minutes' => 15,

    'report_ttl_hours' => 24,

    /*
    | The first tool whose matcher returns arguments wins.
    */
    'tools' => [
        ExportReportTool::class,
        GetOrderTool::class,
        CustomerSummaryTool::class,
        LowStockTool::class,
        SalesSummaryTool::class,
        MyOrdersTool::class,
        SearchOrdersTool::class,
        HelpTool::class,
    ],

];
