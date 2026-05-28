<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AliAgent;
use Illuminate\Http\Request;
use MischaSigtermans\Toon\Facades\Toon;

class AliController extends Controller
{
    public function ali()
    {

        $callData = [
            'lead' => [
                'name' => 'TechCorp Inc.',
                'industry' => 'SaaS',
                'budget_approved' => false,
            ],
            'transcript' => [
                ['speaker' => 'Rep', 'text' => 'Hi Alex, how is TechCorp handling the new server loads?'],
                ['speaker' => 'Lead', 'text' => 'Honestly, it is a mess. Our costs are up 30% and deployments take hours. We are bleeding money.'],
                ['speaker' => 'Rep', 'text' => 'I hear you. If I could show you a way to cut those infrastructure costs in half using our automated pipeline, would you have 10 minutes next Tuesday to look at a demo?'],
                ['speaker' => 'Lead', 'text' => 'Yeah, that sounds interesting. But we do not have budget approval until Q3.'],
                ['speaker' => 'Rep', 'text' => 'Makes total sense. Let us just do a quick intro call next week so you can see if it is even worth pitching to leadership for Q3. Does 2 PM Tuesday work?'],
                ['speaker' => 'Lead', 'text' => 'Sure, send me a calendar invite.'],
            ]
        ];

        $toonEncodedData = Toon::encode($callData);

        $promptTemplate = <<<EOT
You are an expert Sales Coach. Analyze the following sales transcript data.
The data is serialized in a structured text format called TOON.

DATA:
{$toonEncodedData}

Please provide:
1. **Pain Points:** What is the lead's main problem?
2. **Objection Handling:** How well did the Rep handle the budget objection?
3. **Next Steps:** What was the final agreed-upon action?
4. **Score:** Rate the Rep's performance out of 10.
EOT;

        $response = (new AliAgent)->prompt($promptTemplate);

        dd($response);
    }
}
