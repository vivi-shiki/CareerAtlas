<script setup lang="ts">
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
const screen = window.location.pathname.split('/').pop()?.replace('p1-', '') || 'capture'
const titles: Record<string, string> = {
    capture: 'Turn notes into an experience',
    preparation: 'Prepare with your own evidence',
    opportunity: 'Shape a portfolio for this opportunity',
}
</script>
<template>
    <WorkspaceShell
        preview
        :active="
            screen === 'capture'
                ? 'Capture'
                : screen === 'preparation'
                  ? 'Preparation'
                  : 'Opportunities'
        "
    >
        <div class="workspace-page p1-page">
            <nav class="breadcrumbs">Personal workspace / {{ screen }}</nav>
            <PageHeading
                :title="titles[screen]!"
                description="Your sources stay private until you explicitly publish a reviewed selection."
                status="Private draft"
            />
            <div class="p1-grid">
                <section class="p1-card">
                    <template v-if="screen === 'capture'">
                        <h2>Start with what happened</h2>
                        <label
                            >Project<select>
                                <option>Atlas Notes · Frontend developer</option>
                            </select></label
                        >
                        <label
                            >Notes<textarea rows="6">
I built keyboard navigation for project search. Component tests cover focus moving between results. I still need to check the mobile screen reader flow.</textarea
                            >
                        </label>
                        <button class="p1-primary">Save notes</button><button>Draft with AI</button>
                        <p class="muted">
                            Missing facts remain blank. Review suggestions before adding them.
                        </p>
                        <h2>Review the draft</h2>
                        <label>Title<input value="Keyboard navigation for project search" /></label
                        ><label
                            >Personal action<textarea rows="3">
I built keyboard navigation and component tests.</textarea
                            >
                        </label>
                    </template>
                    <template v-else-if="screen === 'preparation'">
                        <h2>Preparation brief</h2>
                        <label>Topic or role<input value="Frontend engineer" /></label
                        ><label
                            >Requirements<textarea rows="4">
Explain accessible interaction design and how you validate your own contribution.</textarea
                            >
                        </label>
                        <div class="p1-row">
                            <label
                                >Target language<select>
                                    <option>日本語</option>
                                </select></label
                            ><label
                                >Answer length<select>
                                    <option>Standard</option>
                                </select></label
                            >
                        </div>
                        <h2>One question at a time</h2>
                        <p>What did you contribute to accessible project search?</p>
                        <label
                            >Your answer<textarea
                                rows="5"
                                placeholder="Write your answer in your own words"
                            ></textarea></label
                        ><button class="p1-primary">Save answer</button
                        ><button>Stop practice</button>
                    </template>
                    <template v-else>
                        <h2>Opportunity brief</h2>
                        <label>Title<input value="Frontend collaboration" /></label
                        ><label>Direction<input value="Accessible product interfaces" /></label
                        ><label
                            >Private requirements<textarea rows="3">
A portfolio focused on interaction design and testing.</textarea
                            >
                        </label>
                        <h2>Selected experiences</h2>
                        <div class="p1-source">
                            <strong>Accessible project search</strong>
                            <p>Atlas Notes · Confirmed source</p>
                            <button>Move up</button><button>Choose fields</button>
                        </div>
                        <label
                            >Introduction<textarea rows="4">
I build accessible interfaces and connect my work to verifiable evidence.</textarea
                            ></label
                        ><button class="p1-primary">Save opportunity</button
                        ><button>Review public preview</button>
                    </template>
                </section>
                <aside class="p1-card p1-context">
                    <h2>Evidence in context</h2>
                    <p>Atlas Notes</p>
                    <div class="p1-source">
                        <strong>Accessible project search</strong>
                        <p>I built keyboard navigation and component tests.</p>
                        <span class="status-badge">Confirmed source</span>
                    </div>
                    <h3>Keep the boundaries clear</h3>
                    <p>
                        Team outcomes and your personal actions are separate. AI suggestions need
                        your review.
                    </p>
                    <p>Source changes are flagged before you confirm or publish.</p>
                </aside>
            </div>
        </div></WorkspaceShell
    >
</template>
