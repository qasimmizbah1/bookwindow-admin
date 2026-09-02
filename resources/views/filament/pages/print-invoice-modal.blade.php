<div style="height: 70vh;">
    <iframe src="{{ route('orders.print', $record->id) }}" style="width: 100%; height: 100%; border: none;" onload="this.contentWindow.focus(); this.contentWindow.print();"></iframe>
</div>
