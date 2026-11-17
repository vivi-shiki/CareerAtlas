<script setup lang="ts">
import { t } from '@/i18n'
import { Plus, Info, ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import EvidenceList from '@/components/EvidenceList.vue'
import SkillChips from '@/components/SkillChips.vue'
import type { PortfolioView } from '@/types/portfolio'

const props = defineProps<{ portfolio: PortfolioView }>()
const project = props.portfolio.projects[0]!
const record = project.experiences[0]!
const contribution = record.contribution.startsWith('Built ')
    ? 'I built the search interface, added keyboard navigation and wrote component tests.'
    : record.contribution
</script>

<template>
    <WorkspaceShell preview>
        <div class="workspace-page editor-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <a href="/design/projects">{{ t('Projects') }}</a
                ><span>/</span><a href="/design/projects">{{ project.title }}</a
                ><span>/</span><strong>{{ t('Experience') }}</strong>
            </nav>
            <PageHeading
                :title="t('Capture your contribution')"
                :description="t('Keep the work, your actions and the evidence connected.')"
                :status="t('Draft · Private')"
            />
            <div class="editor-grid">
                <section class="editor-fields" :aria-label="t('Experience')">
                    <div class="field">
                        <label for="experience-title"
                            >{{ t('Title') }} <span class="required">*</span></label
                        ><Input
                            id="experience-title"
                            :model-value="record.title"
                            :placeholder="t('What did you work on?')"
                        />
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="experience-project"
                                >{{ t('Project') }} <span class="required">*</span></label
                            ><select id="experience-project">
                                <option>{{ project.title }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="record-type"
                                >{{ t('Record type') }} <span class="required">*</span></label
                            ><select id="record-type">
                                <option>{{ t('Work contribution') }}</option>
                                <option>{{ t('Learning') }}</option>
                                <option>{{ t('Problem solving') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="experience-role">{{ t('Role') }}</label
                            ><Input id="experience-role" :model-value="project.role" readonly />
                        </div>
                        <div class="date-fields">
                            <div class="field">
                                <label for="occurrence"
                                    >{{ t('Date') }} <span class="required">*</span></label
                                ><Input id="occurrence" :model-value="record.occurred" />
                            </div>
                            <div class="field">
                                <label for="precision">{{ t('Precision') }}</label
                                ><select id="precision">
                                    <option>{{ t('Month') }}</option>
                                    <option>{{ t('Year') }}</option>
                                    <option>{{ t('Day') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="editor-divider"></div>
                    <div class="field">
                        <label for="contribution"
                            >{{ t('My contribution') }} <span class="required">*</span></label
                        ><Textarea
                            id="contribution"
                            :model-value="contribution"
                            :rows="contribution.length > 300 ? 8 : 3"
                            :placeholder="t('Describe your own actions.')"
                        />
                    </div>
                    <div class="field">
                        <label for="team-outcome"
                            >{{ t('Team outcome') }}
                            <span class="optional">{{ t('(optional)') }}</span></label
                        ><Textarea
                            id="team-outcome"
                            :model-value="record.teamOutcome"
                            :rows="record.teamOutcome && record.teamOutcome.length > 200 ? 4 : 2"
                        />
                    </div>
                    <details class="background-details">
                        <summary>
                            <ChevronDown aria-hidden="true" /><span
                                >{{ t('Background') }}
                                <span class="optional">{{ t('(optional)') }}</span></span
                            >
                        </summary>
                        <label class="sr-only" for="background">{{ t('Background') }}</label
                        ><Textarea id="background" :rows="3" />
                    </details>
                    <p class="info-line">
                        <Info aria-hidden="true" />{{ t('A measurable result is optional.') }}
                    </p>
                    <div class="field">
                        <label>{{ t('Skills') }}</label
                        ><SkillChips :skills="record.skills" editable
                            ><Button variant="outline" class="add-skill"
                                ><Plus data-icon="inline-start" />{{ t('Add skill') }}</Button
                            ></SkillChips
                        >
                    </div>
                </section>
                <aside class="evidence-rail">
                    <h2>{{ t('Supporting evidence') }}</h2>
                    <EvidenceList :evidence="record.evidence" editable />
                    <p class="info-line">
                        <Info aria-hidden="true" />{{
                            t('Links have not been independently verified.')
                        }}
                    </p>
                    <Button variant="outline" class="evidence-add"
                        ><Plus data-icon="inline-start" />{{ t('Add evidence') }}</Button
                    >
                    <p class="privacy-notice">
                        <Info aria-hidden="true" /><span
                            >{{ t('Confirmation keeps this record private.') }}<br />{{
                                t('Publishing is a separate step.')
                            }}</span
                        >
                    </p>
                </aside>
            </div>
        </div>
        <footer class="editor-footer">
            <p>Draft saved · 17 Nov 2026</p>
            <div class="actions">
                <Button variant="outline">{{ t('Save draft') }}</Button
                ><Button>{{ t('Confirm experience') }}</Button>
            </div>
            <small class="fictional-note">Fictional demo data</small>
        </footer>
    </WorkspaceShell>
</template>
