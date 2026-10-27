<?php

declare(strict_types=1);

namespace DbChatbot\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use DbChatbot\Chat\Support\DateRange;
use DbChatbot\Chat\Support\Money;
use DbChatbot\Contracts\ChatUser;
use DbChatbot\Models\Order;
use DbChatbot\Models\Product;
use DbChatbot\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;

final class ReportService
{
    public function order(ChatUser $user, Order $order, string $format): Report
    {
        $lines = [
            'Order '.$order->number.' is '.$order->status->value.'.',
            'Customer: '.($order->customer->name ?? 'Unknown').' ('.($order->customer->email ?? 'unknown').')',
            'Placed: '.($order->placed_at?->toDateTimeString() ?? ''),
            'Subtotal '.Money::format($order->subtotal).', tax '.Money::format($order->tax).', shipping '.Money::format($order->shipping).', total '.Money::format($order->total).'.',
        ];

        if ($order->payment === null) {
            $lines[] = 'Payment: none yet.';
        } else {
            $lines[] = 'Payment: '.$order->payment->method.', '.$order->payment->status->value.', '.Money::format($order->payment->amount).'.';
        }

        if ($order->shipment === null) {
            $lines[] = 'Shipment: not shipped yet.';
        } else {
            $lines[] = 'Shipment: '.$order->shipment->carrier.', tracking '.($order->shipment->tracking_number ?? 'n/a').', '.$order->shipment->status->value.'.';
        }

        foreach ($order->statusTimeline() as $event) {
            $lines[] = $event['label'].($event['at'] !== '' ? ': '.$event['at'] : '');
        }

        return $this->persist($user, 'order', $format, 'db-chatbot::reports.order', [
            'title' => 'Order '.$order->number,
            'lines' => $lines,
            'columns' => [
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'quantity', 'label' => 'Qty'],
                ['key' => 'unit_price', 'label' => 'Unit price'],
                ['key' => 'line_total', 'label' => 'Line total'],
            ],
            'rows' => $order->items->map(fn ($item): array => [
                'product' => $item->product_name,
                'quantity' => (string) $item->quantity,
                'unit_price' => Money::format($item->unit_price),
                'line_total' => Money::format($item->line_total),
            ])->all(),
        ], [
            'order_id' => $order->id,
            'order_number' => $order->number,
        ]);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  array<string, mixed>  $meta
     */
    public function orders(ChatUser $user, Collection $orders, array $meta, string $format): Report
    {
        $lines = ['Orders list'];

        if (isset($meta['status'])) {
            $lines[] = 'Status: '.$meta['status'];
        }

        if (isset($meta['date_label'])) {
            $lines[] = 'Period: '.DateRange::title((string) $meta['date_label']);
        }

        $lines[] = $orders->count().' orders.';

        return $this->persist($user, 'orders', $format, 'db-chatbot::reports.orders', [
            'title' => 'Orders list',
            'lines' => $lines,
            'columns' => [
                ['key' => 'number', 'label' => 'Order'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'placed', 'label' => 'Placed'],
            ],
            'rows' => $orders->map(fn (Order $order): array => [
                'number' => (string) $order->number,
                'customer' => $order->customer?->email ?? '',
                'status' => $order->status->value,
                'total' => Money::format($order->total),
                'placed' => $order->placed_at?->toDateTimeString() ?? '',
            ])->all(),
        ], $meta);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function sales(ChatUser $user, array $summary, string $format): Report
    {
        return $this->persist($user, 'sales', $format, 'db-chatbot::reports.sales', [
            'title' => 'Sales summary',
            'lines' => [
                'Period: '.$summary['title'],
                'From '.$summary['from'].' to '.$summary['to'].'.',
                $summary['count'].' orders, total '.$summary['total'].'.',
                'Includes paid, shipped, and delivered orders.',
            ],
            'columns' => [
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'orders', 'label' => 'Orders'],
                ['key' => 'total', 'label' => 'Total'],
            ],
            'rows' => $summary['rows'],
        ], [
            'date_label' => $summary['label'],
        ]);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  array<string, mixed>  $meta
     */
    public function lowStock(ChatUser $user, Collection $products, array $meta, string $format): Report
    {
        $out = ($meta['out_of_stock'] ?? false) === true;

        return $this->persist($user, 'low_stock', $format, 'db-chatbot::reports.low-stock', [
            'title' => $out ? 'Out of stock' : 'Low stock',
            'lines' => [
                $out ? 'Products with no stock on hand.' : 'Products at or below their reorder level.',
                $products->count().' products.',
            ],
            'columns' => [
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'name', 'label' => 'Product'],
                ['key' => 'stock', 'label' => 'Stock'],
                ['key' => 'reorder_level', 'label' => 'Reorder at'],
                ['key' => 'price', 'label' => 'Price'],
            ],
            'rows' => $products->map(fn (Product $product): array => [
                'sku' => $product->sku,
                'name' => $product->name,
                'stock' => (string) $product->stock,
                'reorder_level' => (string) $product->reorder_level,
                'price' => Money::format($product->price),
            ])->all(),
        ], [
            'out_of_stock' => $out,
        ]);
    }

    public function temporaryUrl(Report $report): string
    {
        return URL::temporarySignedRoute(
            'reports.download',
            now()->addMinutes((int) config('chatbot.report_url_minutes', 15)),
            ['report' => $report->id],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    private function persist(ChatUser $user, string $kind, string $format, string $view, array $data, array $meta): Report
    {
        $format = $format === 'docx' ? 'docx' : 'pdf';
        $filename = $kind.'-'.Str::uuid().'.'.$format;
        $binary = $format === 'docx' ? $this->docx($data) : Pdf::loadView($view, $data)->setPaper('a4')->output();

        Storage::disk('reports')->put($filename, $binary);

        return Report::query()->create([
            'user_id' => $user->id,
            'kind' => $kind,
            'format' => $format,
            'path' => $filename,
            'meta' => $meta,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function docx(array $data): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection();
        $section->addText((string) $data['title'], ['bold' => true, 'size' => 16]);

        foreach ($data['lines'] as $line) {
            $section->addText((string) $line);
        }

        $rows = $data['rows'];
        $columns = $data['columns'];

        if ($rows !== []) {
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
            $table->addRow();

            foreach ($columns as $column) {
                $table->addCell(2200)->addText((string) $column['label'], ['bold' => true]);
            }

            foreach ($rows as $row) {
                $table->addRow();

                foreach ($columns as $column) {
                    $table->addCell(2200)->addText((string) ($row[$column['key']] ?? ''));
                }
            }
        }

        $temp = tempnam(sys_get_temp_dir(), 'dbchat');

        if ($temp === false) {
            throw new RuntimeException('Could not create a temporary report file.');
        }

        $target = $temp.'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($target);
        $binary = file_get_contents($target);
        @unlink($temp);
        @unlink($target);

        if ($binary === false) {
            throw new RuntimeException('Could not read the report file.');
        }

        return $binary;
    }
}
