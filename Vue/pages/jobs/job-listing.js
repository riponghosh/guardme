require('../../bootstrap/google-maps');

new window.App({
    el: '#app',
    data : function(){
        return {
            jobs : {
                data : [],
                loading : false
            },
            pagination : null,
            categories : {
                data : [],
                loading : false
            },
            companies : {
                data : [],
                loading : false
            },
            countries : {
                data : [],
                loading : false
            },
            cities : {
                data : [],
                loading : false
            },
            filter : {
                offer : {
                    min : 0,
                    max : 0
                },
                date : {
                    min : 0,
                    max : 0
                },
                time : {
                    min : 0,
                    max : 0
                },
                job_complete : {
                    min : 0,
                    max : 0
                },
                categories : [],
                companies : 0,
                star : 0,
                day:0,
            },
            selectedJob : null,
            application : {
                job_id : null,
                bid : 0,
            }
        }
    },
    methods : {
        getJobListings : function (filter) {
            const vm = this;

            var url = '/api/jobs/listings';

            var params = {};

            if(filter){
                params = {
                    categories : filter.categories,
                    companies : filter.companies,
                    min_offer : filter.offer.min,
                    max_offer : filter.offer.max,
                    min_date : filter.date.min,
                    max_date : filter.date.max,
                    min_time : filter.time.min,
                    max_time : filter.time.max,
                    min_job_complete : filter.job_complete.min,
                    max_job_complete : filter.job_complete.max,
                    star : filter.star,
                };
            }

            vm.jobs.loading = true;
            console.log(params);
            window.axios.get(url, {params : params})
                .then(function (response) {
                    if(filter) vm.jobs.data = [];
                    vm.jobs.loading = false;
                    console.log(response);
                    response.data.data.forEach(function (job) {

                        var exists = window._.find(vm.jobs.data, function (item) {
                            return item.id === job.id;
                        });

                        if(!exists){
                            vm.jobs.data.push(job);
                        }
                    });
                    vm.pagination = response.data.links;
                })
            ;
        },
        loadCategories : function () {
            var vm = this;
            vm.categories.loading = true;

            window.axios.get('/api/app/categories')
                .then(function (response) {

                    response.data.forEach(function (category) {
                        vm.categories.data.push(category);
                    });

                    setTimeout(function () {
                        $('.ui.checkbox')
                            .checkbox()
                        ;
                    }, 1000);

                    vm.categories.loading = false;
                });
        },
        loadCompanies : function () {
            var vm = this;
            vm.companies.loading = true;

            window.axios.get('/api/app/companies')
                .then(function (response) {

                    response.data.forEach(function (company) {
                        vm.companies.data.push(company);
                    });

                    vm.companies.loading = false;
                });
        },
        loadCounteries : function () {
            var vm = this;
            vm.countries.loading = true;

            window.axios.get('/api/app/counties')
                .then(function (response) {

                    response.data.forEach(function (country) {
                        vm.countries.data.push(country);
                    });

                    vm.countries.loading = false;
                });
        },
        filterJobs : _.debounce(function (newVal) {
            this.getJobListings(newVal)
        }, 2000),
        applyToJob : function (job) {
            if(!this.$root.app.user){
                alert('Please login to place bid');
                window.location.href = window.location.href + '?action=login';
                return;
            }
            this.selectedJob = job;
            this.application.job_id = job.id;

            $('.ui.modal.application')
                .modal('show')
            ;
        },
        submitApplication : function (job) {
            const vm = this;

            this.selectedJob = job;
            this.application.job_id = job.id;
            vm.$root.ukNotify('submitting application...');


            window.axios.post('/api/jobs/' + vm.selectedJob.id + '/apply', vm.application)
                .then(function (response) {
                    vm.deselectJob();
                    vm.$root.ukNotify('Application successfully submitted!');
                });

            var applied_job = window._.find(vm.jobs.data, function (item) {
                return item.id === vm.selectedJob.id;
            });
            applied_job.applied = true;
        },
        deselectJob : function () {

           /* $('.ui.modal.application')
                .modal('hide')
            ;*/
            this.selectedJob = null;
            this.application.job_id = null;
            this.application.bid = 0;
        }
    },
    components : {

    },
    watch : {
        filter : {
            handler : function (newVal, oldVal) {
                this.filterJobs(newVal);
            },
            deep : true
        }
    },
    mounted : function(){
        const vm = this;
        this.loadCategories();
        this.loadCompanies();
        this.loadCounteries();
        this.getJobListings();


        $(".offer_range_slider").ionRangeSlider({
            type: "double",
            prefix: "£",
            min: 0,
            max: 15,
            onChange: function (data) {
                vm.filter.offer.min = data.from;
                vm.filter.offer.max = data.to;
            },
        });

        $( "#min_date" ).change(function() {
          vm.filter.date.min = $(this).val();
        });

        $( "#max_date" ).change(function() {
          vm.filter.date.max = $(this).val();
        });
        $( "#min_time" ).change(function() {
          vm.filter.time.min = $(this).val();
        });

        $( "#max_time" ).change(function() {
          vm.filter.time.max = $(this).val();
        });
        $( "#input" ).change(function() {
            vm.filter.star=$(this).val();
        });
        $('input[name=day]').change(function(){

            vm.filter.day= $('input[name=day]:checked').val();
        })



    }
});