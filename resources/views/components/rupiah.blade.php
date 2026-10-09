@props(['nilai'])
<span {{ $attributes }}>Rp {{ number_format((int) $nilai, 0, ',', '.') }}</span>
