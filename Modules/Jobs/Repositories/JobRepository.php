<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 25/01/2018
 * Time: 04:28 PM
 */

namespace Modules\Jobs\Repositories;


use Modules\Account\Models\Role;
use Modules\Jobs\Events\JobWasCreated;
use Modules\Jobs\Models\Job;
use Modules\Users\Models\User;

class JobRepository
{
    /**
     * @var Job
     */
    private $job;




    /**
     * JobRepository constructor.
     * @param Job $job
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    /**
     * @param array $data
     * @return Job
     */
    public function saveJob(array $data)
    {
        $job = $this->job->create([
            'company_id' => $data['company'],
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'description' => $data['description'],
            'starts' => to_db_datetime($data['date']['start']),
            'ends' => to_db_datetime($data['date']['end']),
            'offer' => $data['offer'],
            'rating' => $data['rating'],
            'postcode' => $data['postcode'],
            'metadata' => json_encode([
                'broadcasts_config' => $data['broadcastsConfig'],
                'address' => $data['address'],
                'location' => [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]
            ])
        ]);

        publish(new JobWasCreated($job, $data));

        return $job;
    }

    private function generateUniqueSlug($title)
    {
        $generated_slug = str_slug($title);

        while ($this->job->where('slug', $generated_slug)->count()){
            $generated_slug = str_slug($title . ' ' . str_random(3));
        }

        return $generated_slug;
    }

    /**
     * @param $jobSlug
     * @return Job | null
     */
    public function getJobBySlug($jobSlug)
    {
        return $this->job->where('slug', $jobSlug)->first();
    }

    public function getJobListings($limit = 10)
    {
        $query = $this->job
            ->latest();

        if(request('categories')){
            $categories_ids = request('categories');
            $query = $query->whereHas('categories', function ($query) use ($categories_ids){
                $query->whereIn('category_id', $categories_ids);
            });
        }

        if(request('companies')){
            $query = $query->where('company_id', '=', request('companies'));
        }
        if(request('star')){
            $query = $query->where('rating', '=', request('star'));
        }

        if(request('min_offer')){
            $query = $query->where('offer', '>=', request('min_offer'));
        }
        if(request('max_offer')){
            $query = $query->where('offer', '<=', request('max_offer'));
        }

        if(request('min_job_complete')){
            $query = $query->where('completed_at', '>=', request('min_job_complete'));
        }
        if(request('max_job_complete')){
            $query = $query->where('completed_at', '<=', request('max_job_complete'));
        }

        if(request('min_date')){
            $query = $query->where('starts', '>=', request('min_date'));
        }
        if(request('max_date')){
            $query = $query->where('ends', '<=', request('max_date'));
        }

        if(request('min_time')){
            $time = date('i', strtotime(request('min_time')));
            $newtimestamp = strtotime(request('min_date').'+ '.$time.' minute');
            $datetime= date('Y-m-d H:i:s', $newtimestamp);
            $query = $query->where('starts', '>=', $datetime);
        }
        if(request('max_time')){
            $time = date('i', strtotime(request('max_time')));
            $newtimestamp = strtotime(request('max_date').'+ '.$time.' minute');
            $datetime= date('Y-m-d H:i:s', $newtimestamp);
            $query = $query->where('ends', '<=', $datetime);
        }

        if(request('day')){
            $date = new DateTime(date('Y-m-d')." 00:00:00");
            //$date->modify("+23 hours");
            //$date->modify("+59 hours");
            $start= $date->format("Y-m-d H:i:s");
            $date = new DateTime(date('Y-m-d')." 23:59:59");
            $end= $date->format("Y-m-d H:i:s");
            $query = $query->where('starts', '>=', $start);
            $query = $query->where('ends', '<=', $end);
        }


        // for search page

        if(request('search_type')&&request('search_string')){
            $query = $query->where('title', 'like', '%' . request('search_string') . '%');
        }

        if(request('sort')){
            if (request('sort')=='Ending Soon') {
                $query = $query->orderBy('ends', 'asc');
            }
            if (request('sort')=='Newly Added') {
                $query = $query->orderBy('created_at', 'asc');
            }
            if (request('sort')=='Starting Soon') {
                $query = $query->orderBy('starts', 'asc');
            }
            if (request('sort')=='Height Offer') {
                $query = $query->orderBy('offer', 'desc');
            }
            if (request('sort')=='Lowest Offer') {
                $query = $query->orderBy('offer', 'asc');
            }
            if (request('sort')=='Height Application') {
                // $query = $query->orderBy('created_at', 'asc')
            }
            if (request('sort')=='Lowest Application') {
                // $query = $query->orderBy('created_at', 'asc')
            }
        }

        return $query->simplePaginate($limit);
    }

    /**
     * @param $job_id
     * @return Job
     */
    public function getJobById($job_id)
    {
        return $this->job->find($job_id);
    }

    public function getJobApplicants($job_id, $limit = 10, &$total_applicants = 0)
    {
        $query = $this->job
            ->find($job_id)
            ->applicants();

        $total_applicants = $query->count();

        return $query->simplePaginate($limit);
    }

    public function getJobEmployees($job_id, $limit = 10, &$total_employees = 0)
    {
        $query = $this->job
            ->find($job_id)
            ->employees();

        $total_employees = $query->count();

        return $query->simplePaginate($limit);
    }

    /**
     * Gets available jobs for a user
     * for instance:
     * (employer) = gets jobs created by the employer
     * (job seeker) = gets jobs associated to the job seeker
     *
     * @param User|null $user
     * @return array
     */
    public function getUserActiveJobs(User $user = null, $limit = 10, &$total_active_jobs = 0)
    {
        if(!$user) $user = auth()->user();

        /**
         * @var Role $primaryRole
         */
        $primaryRole = $user->getPrimaryRole();

        $query = null;

        switch ($primaryRole->name){
            case config('guardme.acl.Employer'):
                $query = $user->createdJobs()->latest();
                break;
            case config('guardme.acl.Job_Seeker'):
                $query = $user->appliedJobs()->latest();
                break;
            default:
                $query = $this->job->latest();
                break;
        }

        if($query){
            if(request('keyword')){
                $query = $query
                    ->where('title', 'LIKE', '%' . request('keyword') . '%');
            }

            $total_active_jobs = $query->count();

            return $query->simplePaginate($limit);
        }
        return collect([]);
    }

    /**
     * Counts available jobs for a user
     * for instance:
     * (employer) = gets jobs created by the employer
     * (job seeker) = gets jobs associated to the job seeker
     *
     * @param User|null $user
     * @return int
     */
    public function getUserActiveJobStatistics(User $user = null)
    {
        if(!$user) $user = auth()->user();

        /**
         * @var Role $primaryRole
         */
        $primaryRole = $user->getPrimaryRole();

        $count = 0;


        switch ($primaryRole->name){
            case config('guardme.acl.Employer'):
                $count = $user->createdJobs()->count();
                break;
            case config('guardme.acl.Job_Seeker'):
                $count = $user->appliedJobs()->latest()->count();
                break;
        }

        return $count;
    }
}