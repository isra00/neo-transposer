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
						<form method="post" action="{{ route('wizard_select_standard', ['locale' => app()->getLocale()]) }}">
							@csrf
							<input type="hidden" name="gender" value="male">
							<button type="submit" class="flatbutton red">
								@lang('Neither higher nor lower than most of men / I don\'t know')
							</button>
						</form>
					</li>
					<li>
						<form method="post" action="{{ route('wizard_select_standard', ['locale' => app()->getLocale()]) }}">
							@csrf
							<input type="hidden" name="gender" value="male_high">
							<button type="submit" class="flatbutton red">
								@lang('My voice is higher than most of men')
							</button>
						</form>
					</li>
					<li>
						<form method="post" action="{{ route('wizard_select_standard', ['locale' => app()->getLocale()]) }}">
							@csrf
							<input type="hidden" name="gender" value="male_low">
							<button type="submit" class="flatbutton red">
								@lang('My voice is lower than most of men')
							</button>
						</form>
					</li>
				</ul>

				<ul id="sub-female" class="sub-gender">
					<li>
						<form method="post" action="{{ route('wizard_select_standard', ['locale' => app()->getLocale()]) }}">
							@csrf
							<input type="hidden" name="gender" value="female">
							<button type="submit" class="flatbutton red">
								@lang('Neither higher nor lower than most of women / I don\'t know')
							</button>
						</form>
					</li>
					<li>
						<form method="post" action="{{ route('wizard_select_standard', ['locale' => app()->getLocale()]) }}">
							@csrf
							<input type="hidden" name="gender" value="female_high">
							<button type="submit" class="flatbutton red">
								@lang('My voice is higher than most of women')
							</button>
						</form>
					</li>
					<li>
						<form method="post" action="{{ route('wizard_select_standard', ['locale' => app()->getLocale()]) }}">
							@csrf
							<input type="hidden" name="gender" value="female_low">
							<button type="submit" class="flatbutton red">
								@lang('My voice is lower than most of women')
							</button>
						</form>
					</li>
				</ul>
			</section>

		</div><!-- /.two-sliding-panels -->
	</div><!-- /.clearfix -->

@endsection

@section('scripts')
	<script>
	NT = {
		selectGender: function(oLinkClicked) {
			$(oLinkClicked.parentNode).addClass('slided');
			$('.sub-gender').hide();
			document.getElementById(oLinkClicked.dataset.show).style.display = 'block';
		}
	};
	</script>
@endsection
