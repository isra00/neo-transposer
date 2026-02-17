@extends('_base')

@section('content')

	<h1>@lang('Voice measure wizard')</h1>

	<p>@lang('Follow these steps and the application will estimate your highest and lowest note. If you don\'t follow properly these steps you won\'t be able to use Neo-Transposer.')</p>

	<h2 class="step-1">@lang('Step 1')</h2>

	<p>@lang('To start, choose one of these options:')</p>

	<div class="clearfix">

		<div class="two-sliding-panels">

			<section class="two-choices gender-selection" id="gender-selection">
				<a class="flatbutton red" href="javascript:void(0)" onclick="NT.selectGender(this)" data-show="sub-male">
					@lang('I have <span>male</span> voice')
				</a>
				<a class="flatbutton red" href="javascript:void(0)" onclick="NT.selectGender(this)" data-show="sub-female">
					@lang('I have <span>female</span> voice')
				</a>
			</section>

			<section class="sub-gender-selection">

				<ul id="sub-male" class="sub-gender">
					<li>
						<a class="flatbutton red" href="{{ route('wizard_select_standard', ['locale' => app()->getLocale(), 'gender' => 'male']) }}">
							@lang('Neither higher nor lower than most of men / I don\'t know')
						</a>
					</li>
					<li>
						<a class="flatbutton red" href="{{ route('wizard_select_standard', ['locale' => app()->getLocale(), 'gender' => 'male_high']) }}">
							@lang('My voice is higher than most of men')
						</a>
					</li>
					<li>
						<a class="flatbutton red" href="{{ route('wizard_select_standard', ['locale' => app()->getLocale(), 'gender' => 'male_low']) }}">
							@lang('My voice is lower than most of men')
						</a>
					</li>
				</ul>

				<ul id="sub-female" class="sub-gender">
					<li>
						<a class="flatbutton red" href="{{ route('wizard_select_standard', ['locale' => app()->getLocale(), 'gender' => 'female']) }}">
							@lang('Neither higher nor lower than most of women / I don\'t know')
						</a>
					</li>
					<li>
						<a class="flatbutton red" href="{{ route('wizard_select_standard', ['locale' => app()->getLocale(), 'gender' => 'female_high']) }}">
							@lang('My voice is higher than most of women')
						</a>
					</li>
					<li>
						<a class="flatbutton red" href="{{ route('wizard_select_standard', ['locale' => app()->getLocale(), 'gender' => 'female_low']) }}">
							@lang('My voice is lower than most of women')
						</a>
					</li>
				</ul>
			</section>

		</div><!-- /.two-sliding-panels -->
	</div><!-- /.clearfix -->

@endsection

@section('scripts')
	<script>
	NT = {
		selectGender: function(oLinkClicked)
		{
			$(oLinkClicked.parentNode).addClass('slided');
			$('.sub-gender').hide();
			document.getElementById(oLinkClicked.dataset.show).style.display = 'block';
		}
	};
	</script>
@endsection
