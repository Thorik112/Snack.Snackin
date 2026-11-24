<div class="grid4">
@foreach($stat as $s)
  <div class="card stat {{ isset($s['sorot']) ? 'sorot' : '' }}">
    <div class="sh"><span class="lbl">{{ $s['label'] }}</span><span class="dl" data-delta="{{ $s['key'] }}"></span></div>
    <span class="num" data-stat="{{ $s['key'] }}">&nbsp;</span>
    <div class="sparkbox" data-spark="{{ $s['key'] }}"></div>
  </div>
@endforeach
</div>
