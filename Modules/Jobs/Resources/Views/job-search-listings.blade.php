@extends('app::layouts.site')

@section('content')
   <div class="content-wrap">
      <div class="container clearfix">
      	<div class="col-md-12 form-inline">
      		
	      	<div class="input-group col-md-8" style="margin-top: 50px;margin-bottom: 50px;">
              <div class="" style="z-index: 22;position: absolute;">
                  <select id="search_type2" onchange="document.getElementById('search_field2').setAttribute('placeholder',this.value)" style="width: 19px;height: 35px" v-model="filter.search_type">
                        <option value="Find Jobs" @if($search_type=="Find Jobs") {'selected'} @endif>Find Jobs</option>
                        @if($search_type=="Freelancers")
                        <option value="Freelancers" selected>Freelancers</option>
                        <option value="Find Jobs">Find Jobs</option>
                        @else
                        <option value="Freelancers">Freelancers</option>
                        <option value="Find Jobs" selected>Find Jobs</option>
                        @endif
                    </select>
              </div>        
              <input type="text" class="form-control" name="x" id="search_field2" placeholder="Find Jobs" style="padding-left: 5%;height: 35px" onkeypress="if(event.keyCode==13){window.location.assign('/jobs/search/'+this.getAttribute('placeholder')+'/'+this.value)}" value="{{$search_string}}" v-model="filter.search_string">
              <span class="">
                  <button class="btn btn-default hidden" type="button" style="right: 0px;"><span class="icon-search3"></span></button>
              </span>
          </div>
	      	<div class="input-group col-md-4" style="margin-top: 50px;margin-bottom: 50px;"> 
	      		<div class="form-inline">
					<label for="inputState">Sort By: </label>
					<select id="inputState" class="form-control" v-model="filter.sort">
						<option value=""></option>
						<option value="Ending Soon">Ending Soon</option>
						<option value="Newly Added">Newly Added</option>
						<option value="Starting Soon">Starting Soon</option>
						<option value="Height Offer">Height Offer</option>
						<option value="Lowest Offer">Lowest Offer</option>
						<option value="Height Application">Height Application</option>
						<option value="Lowest Application">Lowest Application</option>
					</select>
				</div>
	      	</div>
      	</div>
         <div class="col_three_fifth nobottommargin listings">
            <div class="listing-item" v-for="job in jobs.data" :class="{'ui loading' : jobs.loading}">

               <div class="row">
                  <div class="col-sm-9">
                     <div class="fancy-title title-bottom-border">
                        <h3 class="uk-text-truncate">
                           <a :href="'/jobs/' + job.slug">
                              @{{ job.title }} 
                           </a>
                        </h3>
                     </div>

                     <div class="mb-3 mt-0">
                        <span v-for="category in job.categories" class="d-inline-block mr-3">
                           <i class="tag icon"></i>
                           @{{ category.name }}
                        </span>
                     </div>

                     <p class="list-description" v-html="job.description"></p>

                     <div class="mt-3">
                        @if(hasRole(config('guardme.acl.Job_Seeker')))
                        <a class="ui label tiny black"
                           v-if="!job.applied"
                           @click="submitApplication(job)">
                           Apply Now
                        </a>
                           <span v-else class="ui success image label tiny">
                              <i class="icon check"></i> applied
                           </span>
                        @endif

                        <span class="ui blue image label tiny">
                           £@{{ job.offer }}
                           <div class="detail">Hourly</div>
                        </span>

                        <a :href="'/jobs/' + job.slug" class="ui label tiny green">
                           <i class="eye icon"></i>
                           See details
                        </a>
                     </div>
                  </div>
               </div>

               <div class="divider divider-short"><i class="icon-star3"></i></div>
            </div>
         </div>

         <div class="col_two_fifth nobottommargin col_last px-5">

            <div class="col_full">
               <h5>Apply Filter:</h5>
            </div>

            <div class="col_full">
               <label>Offer:</label>
               <input class="offer_range_slider" />
            </div>
            <div class="col_full">
               <label>Date Range:</label>
               <div class="input-daterange input-group">
                  
                  <input type="date" name="min_date" value="" id="min_date" class="form-control"/>

                  <div class="input-group-prepend"><div class="input-group-text">to</div></div>
                  <input type="date" name="max_date" value="" id="max_date" class="form-control"/>
               </div>
            </div>
            <div class="col_full">
               <label>Time Range:</label>
               <div class="input-daterange input-group">
                  
                  <input type="time" name="min_time" value="" id="min_time" class="form-control"/>

                  <div class="input-group-prepend"><div class="input-group-text">to</div></div>
                  <input type="time" name="max_time" value="" id="max_time" class="form-control" />
               </div>
            </div>

            <div class="col_full">
               <label>Jobs Completed Range:</label>
               <div class="input-daterange input-group">
                  <input type="date" value="" class="form-control tleft" placeholder="MM/DD/YYYY" v-model="filter.job_complete.min">
                  <div class="input-group-prepend"><div class="input-group-text">to</div></div>
                  <input type="date" value="" class="form-control tleft" placeholder="MM/DD/YYYY" v-model="filter.job_complete.max">
               </div>
            </div>

            <div class="col_full">
               <label>Star Rating Range:</label>
               <div class="white-section">
                  <input id="input" type="number" class="rating" max="5" name="star" data-size="sm" style="overflow: hidden;" v-model="filter.star">
               </div>
            </div>

            <div class="col_full">
               <label>Day:</label>
               <div class="form-check">
                  <input class="form-check-input" type="radio" name="day" id="single_day" value="1">
                  <label class="form-check-label" for="single_day">
                     Single day
                  </label>
               </div>
               <div class="form-check">
                  <input class="form-check-input" type="radio" name="day" id="multiple_day" value="0">
                  <label class="form-check-label" for="multiple_day">
                     Multiple days
                  </label>
               </div>
            </div>

            <div class="col_full">
               <label>Category: <small class="uk-text-meta">(Choose all that apply)</small></label>
               <div class="fluid d-flex justify-content-between row">
                  <div class="inline field col-6" v-for="category in categories.data">
                     <div class="ui checkbox">
                        <input type="checkbox" :value="category.id" name="categories"
                               tabindex="0" class="hidden" v-model="filter.categories">
                        <label>@{{ category.name }}</label>
                     </div>
                  </div>
               </div>
            </div>

            <div class="col_full">
               <label>Country:</label>
               <div class="form-check" v-for="country in countries.data">
                  <input class="form-check-input" type="radio" name="country" :id="'country_'+country.id" :value="country.id">
                  <label class="form-check-label" :for="'country_'+country.id">
                     @{{ country.name }}
                  </label>
               </div>
            </div>
            <div class="col_full">
               <label>Cities:</label>
               <div class="form-check" v-for="city in cities.data">
                  <input class="form-check-input" type="radio" name="city" :id="'city_'+city.id" :value="city.id">
                  <label class="form-check-label" :for="'city_'+city.id">
                     @{{ city.name }}
                  </label>
               </div>
            </div>

         </div>
      </div>
   </div>

   <div class="ui modal small application" style="bottom: unset;">
      <div class="header">Apply to job</div>
      <div class="content">
         <form class="ui form px-3"
               @submit.prevent="submitBid()"
               data-vv-scope="application-form">
            <div class="row">
               <div class="col-sm-6">
                  <h3>
                     @{{ selectedJob ? selectedJob.title : ''}}
                  </h3>
                  <p v-html="selectedJob ? selectedJob.description : ''"></p>
               </div>
               <div class="col-sm-5 offset-1">
                  <div class="row">
                     <div class="col_full">
                        <label class="t400">Offer (£):</label>
                        <input type="text" placeholder="" :value="selectedJob ? selectedJob.offer : 0"
                               disabled class="form-control" />
                     </div>

                     <div class="col_full">
                        <label class="t400">Your bid (£):</label>
                        <input type="text" name="bid" placeholder=""
                               v-model.number="application.bid" class="form-control" />
                     </div>

                     <div class="field my-3">
                        <button class="ui button primary float-none mini" type="submit">
                           Submit Bid <i class="icon check circle"></i>
                        </button>
                     </div>
                  </div>
               </div>
            </div>
         </form>
      </div>
   </div>
@endsection

@section('feature')
   <!-- Page Title
		============================================= -->
   <section id="page-title" class="page-title-parallax page-title-dark page-title-center"
            style="background: url('/assets/img/security_lady_man.jpg') no-repeat center center / cover; padding: 90px 0;"
            data-stellar-background-ratio="1">

      <div class="container clearfix">
         <h1>Job Listings</h1>
         <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">Job Listing</li>
         </ol>
      </div>

      <div class="video-wrap" style="position: absolute; top: 0; left: 0; height: 100%; z-index:1;">
         <div class="video-overlay" style="background: rgba(0,0,0,0.8);"></div>
      </div>

   </section><!-- #page-title end -->
@endsection

@push('scripts')
   <script src="/build/js/jobs/job-search-listings.min.js"></script>
   <script src="/build/js/star-rating.js"></script>

   <script>

   </script>
@endpush

@push('styles')
<link rel="stylesheet" href="/build/css/bs-rating.css" type="text/css" />

@endpush