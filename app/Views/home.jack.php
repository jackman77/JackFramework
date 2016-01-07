<html>

<h1>Judul</h1>

<?php echo 123123; ?>
@php
$a = range  (1,10);
@endphp

@foreach ($a as $s)
{{ $s }}

@endforeach
{{ csrf() }}
</html>