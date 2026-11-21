@extends('_base')

@section('content')

<h3 class="section-title">POINT D'(IN)FORMATION</h3>

<div class="inside">

	<h1>Que sont les transpositions compatibles avec l'assemblée ?</h1>

	<p>Vous savez déjà que Neo-Transposer transpose les <a href="https://neocatechumenaleiter.org/fr/evangelisation/nouvelle-esthetique/#sec-cantos">chants du Chemin</a> pour votre voix, ou, en termes techniques, pour votre tessiture. La tessiture correspond à l'ensemble des notes que vous êtes capable de chanter, de la plus grave à la plus aiguë.</p>

	<p>Jusqu'à présent, chaque fois que vous utilisiez Neo-Transposer pour transposer un chant, l'application affichait ce message :</p>

	<div class="tip from-outside">@lang('Beware that this is the best key for your voice, but might not be the best one for the assembly.')</div>

	<p>En effet, vous vous êtes peut-être rendu compte qu'après avoir transposé certains chants, il est facile pour vous de les chanter confortablement, mais que le reste de l'assemblée a des difficultés à chanter le refrain, parce qu'il est trop aigu ou trop grave. Cela s'explique par le fait que chaque personne a sa propre tessiture : elle varie non seulement entre hommes et femmes, mais aussi d'un homme à l'autre et d'une femme à l'autre. C'est pourquoi il est naturel que lorsque vous avez transposé un chant pour <em>votre</em> voix, ce soit confortable pour vous, mais pas pour les autres.</p>

	<p>Comment résoudre ce problème ? Cela dépend du chant. Pour certains chants, il est impossible de rendre compatible la tessiture du chantre avec celle de l'assemblée. Pour d'autres, Neo-Transposer a développé les <strong>transpositions compatibles avec l'assemblée</strong>. Ces transpositions tiennent compte non seulement de la tessiture du chantre et du chant, mais aussi de celle de l'assemblée et des parties du chant qu'elle chante (habituellement le refrain). En combinant ces quatre tessitures, Neo-Transposer calcule des accords qui, tout en restant dans la tessiture du chantre, permettent au refrain de rester également dans celle de l'assemblée.</p>

	<p class="center"><img src="{{ request()->getBasePath() }}/static/img/perfect-key-pc.fr.gif" alt="Idéalement, la tonalité parfaite d'un chant est l'intersection entre la tonalité la plus confortable pour le chantre, la plus confortable pour l'assemblée et celle qui reflète le mieux l'intention du chant." /></p>

	<p>Gardez à l'esprit qu'il ne s'agit que d'une approximation. Dans l'assemblée, chaque personne a une tessiture différente. Mais selon mon expérience personnelle de chantre, j'ai défini une tessiture standard que la plupart des gens (hommes et femmes) sont capables de chanter. Cette tessiture standard est une estimation, toujours imparfaite. Il s'agit de Si2 &rarr; Si3, en supposant que les femmes chanteront une octave au-dessus des hommes (c.-à-d. Si3 &rarr; Si4).</p>

	<p>Lorsque Neo-Transposer transpose un chant pour votre voix et celle de l'assemblée, l'un de ces trois cas peut se produire :</p>

	<p>1) Dans le meilleur des cas, la transposition adaptée <strong>à votre voix</strong> est déjà, en soi, compatible avec la tessiture standard de l'assemblée. Si c'est le cas, vous verrez ce message :</p>

	<p class="tip people-compatible star from-outside">@lang('With these chords the assembly too will be able to sing the song comfortably.') <small><a href="javascript:void(0)">@lang('Learn more')</a></small></p>

	<p>2) La transposition adaptée <strong>à votre voix</strong> est trop grave ou trop aiguë pour l'assemblée. Dans ce cas, Neo-Transposer vous proposera une autre transposition qui sera plus aiguë ou plus grave que la vôtre, afin de convenir aussi à l'assemblée. Cette transposition (que nous appelons « compatible avec l'assemblée ») <strong>est dans votre tessiture, ce qui signifie que vous pouvez la chanter</strong>, bien qu'elle soit un peu plus grave ou plus aiguë que la première, calculée sans tenir compte de l'assemblée. Neo-Transposer l'indiquera alors ainsi :</p>

	<p class="explanation people-compatible star from-outside">@lang('This other transposition, though a bit :difference, fits well the people of the assembly.', ['difference' => __('lower')])</p>

	<p>3) Certains chants ont une tessiture plus large que la tessiture standard de l'assemblée. C'est-à-dire que quels que soient les accords utilisés, il est pratiquement impossible pour toute l'assemblée de chanter le refrain confortablement.</p>

	<p class="explanation people-compatible from-outside">@lang('The chords given above are good for your voice, but probably too high for the assembly. The following transposition is :difference, though still high for some people of the assembly.', ['difference' => __('lower')])</p>

	<p>Il existe d'autres cas, mais le but de cet article n'est pas d'expliquer le fonctionnement interne de Neo-Transposer en détail.</p>

	<p>Rappelez-vous que tout ce mécanisme n'est qu'une approximation toujours imparfaite. La tessiture standard que j'ai définie pour l'assemblée n'est jamais exacte, et il peut y avoir des communautés qui chantent les chants différemment... en bref : Neo-Transposer ne fait qu'une approximation et n'est jamais fiable à 100 %. Même moi, je chante certains chants avec des accords différents de ceux que donne l'application, car <strong>une machine ne peut pas faire le travail d'une personne</strong>, du moins dans ce cas. Vous devriez tester chaque transposition et vérifier si elle vous convient réellement, ou changer le capo... Et s'il vous plaît, si cela fonctionne, cliquez sur le bouton vert !</p>

	<p>Si vous avez des questions ou souhaitez en savoir plus, écrivez-moi à <a href="mailto:neo-transposer@mail.com" class="inline-block">neo-transposer@mail.com</a>.</p>

	<p class="article-footer"><time datetime="2017-08-11T12:30">11 août 2017</time></p>

</div>

@endsection
